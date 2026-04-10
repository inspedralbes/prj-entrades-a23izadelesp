<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSession;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\Zone;
use App\Jobs\ProcessPayment;
use App\Services\QrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:app_sessions,id',
            'identifier' => 'required|string',
            'seats' => 'array',
            'seats.*.row' => 'required',
            'seats.*.col' => 'required|integer',
            'zones' => 'array',
            'zones.*.zone_id' => 'required|exists:zones,id',
            'zones.*.quantity' => 'required|integer|min:1|max:10',
            'zones.*.lock_id' => 'required|string',
            'zone_seats' => 'array',
            'zone_seats.*.zone_id' => 'required|exists:zones,id',
            'zone_seats.*.row' => 'required|integer|min:0',
            'zone_seats.*.col' => 'required|integer|min:0',
            'guest_email' => 'nullable|email',
        ]);

        $user = Auth::user();
        $guestEmail = $validated['guest_email'] ?? ($user?->email);

        if (!$user && !$guestEmail) {
            return response()->json(['error' => 'Email requerit per a compra guest'], 422);
        }

        $session = AppSession::query()->with('event')->findOrFail($validated['session_id']);

        $total = 0;
        $seats = $validated['seats'] ?? [];
        $zones = $validated['zones'] ?? [];
        $zoneSeats = $validated['zone_seats'] ?? [];
        $identifier = $validated['identifier'];

        $unavailableSeats = [];
        $unavailableZones = [];
        $unavailableZoneSeats = [];

        if (empty($seats) && empty($zones) && empty($zoneSeats)) {
            return response()->json(['error' => 'Has de seleccionar seients o zones'], 422);
        }

        foreach ($seats as $seat) {
            $lockKey = "seat:lock:{$validated['session_id']}:{$seat['row']}:{$seat['col']}";
            $owner = Redis::get($lockKey);

            if ($owner === null || $owner !== $identifier) {
                $unavailableSeats[] = [
                    'row' => $seat['row'],
                    'col' => $seat['col'],
                ];
                continue;
            }

            $total += (float) $session->price;
        }

        foreach ($zones as $zoneRequest) {
            $zone = Zone::query()->where('session_id', $session->id)->find($zoneRequest['zone_id']);
            $lockKey = "zone:lock:{$validated['session_id']}:{$zoneRequest['zone_id']}:{$zoneRequest['lock_id']}";
            $data = Redis::get($lockKey);
            $decoded = $data ? json_decode($data, true) : null;

            if (
                !$zone
                || $zone->zone_type !== 'general_admission'
                || !$decoded
                || ($decoded['identifier'] ?? null) !== $identifier
                || (int) ($decoded['quantity'] ?? 0) < (int) $zoneRequest['quantity']
            ) {
                $unavailableZones[] = [
                    'zone_id' => $zoneRequest['zone_id'],
                    'requested' => (int) $zoneRequest['quantity'],
                    'locked' => (int) ($decoded['quantity'] ?? 0),
                ];
                continue;
            }

            $total += ((float) $zone->price) * ((int) $zoneRequest['quantity']);
        }

        foreach ($zoneSeats as $zoneSeat) {
            $zone = Zone::query()->where('session_id', $session->id)->find($zoneSeat['zone_id']);
            $lockKey = "zone-seat:lock:{$validated['session_id']}:{$zoneSeat['zone_id']}:{$zoneSeat['row']}:{$zoneSeat['col']}";
            $data = Redis::get($lockKey);
            $decoded = $data ? json_decode($data, true) : null;

            if (
                !$zone
                || $zone->zone_type !== 'seated'
                || !$decoded
                || ($decoded['identifier'] ?? null) !== $identifier
            ) {
                $unavailableZoneSeats[] = [
                    'zone_id' => $zoneSeat['zone_id'],
                    'row' => $zoneSeat['row'],
                    'col' => $zoneSeat['col'],
                ];
                continue;
            }

            $total += (float) $zone->price;
        }

        if (!empty($unavailableSeats) || !empty($unavailableZones) || !empty($unavailableZoneSeats)) {
            return response()->json([
                'error' => 'Alguns seients o zones ja no estan disponibles',
                'unavailable_seats' => $unavailableSeats,
                'unavailable_zones' => $unavailableZones,
                'unavailable_zone_seats' => $unavailableZoneSeats,
            ], 422);
        }

        $booking = Booking::create([
            'user_id' => $user?->id,
            'guest_email' => $guestEmail,
            'identifier' => $identifier,
            'session_id' => $validated['session_id'],
            'status' => 'pending',
            'total' => $total,
        ]);

        foreach ($seats as $seat) {
            Ticket::create([
                'booking_id' => $booking->id,
                'row' => $seat['row'],
                'col' => $seat['col'],
            ]);
        }

        foreach ($zones as $zoneRequest) {
            for ($i = 0; $i < ((int) $zoneRequest['quantity']); $i++) {
                Ticket::create([
                    'booking_id' => $booking->id,
                    'zone_id' => $zoneRequest['zone_id'],
                ]);
            }
        }

        foreach ($zoneSeats as $zoneSeat) {
            Ticket::create([
                'booking_id' => $booking->id,
                'zone_id' => $zoneSeat['zone_id'],
                'row' => $zoneSeat['row'],
                'col' => $zoneSeat['col'],
            ]);
        }

        foreach ($zones as $zoneRequest) {
            Redis::setex("booking:zone_lock:{$booking->id}:{$zoneRequest['zone_id']}", 3600, $zoneRequest['lock_id']);
        }

        foreach ($zoneSeats as $zoneSeat) {
            Redis::setex(
                "booking:zone_seat:{$booking->id}:{$zoneSeat['zone_id']}:{$zoneSeat['row']}:{$zoneSeat['col']}",
                3600,
                '1'
            );
        }

        foreach ($seats as $seat) {
            Redis::setex(
                "booking:grid_seat:{$booking->id}:{$seat['row']}:{$seat['col']}",
                3600,
                '1'
            );
        }

        if ($request->filled('socket_id')) {
            Redis::setex("booking_socket:{$booking->id}", 3600, (string) $request->input('socket_id'));
        }

        if ($request->boolean('sync', false)) {
            ProcessPayment::dispatchSync($booking->id);
        } else {
            Redis::rpush('purchase:queue', $booking->id);
            ProcessPayment::dispatch($booking->id);
        }

        $booking->refresh();

        return response()->json([
            'data' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'total' => $booking->total,
                'message' => 'Reserva en procés',
            ]
        ], 201);
    }

    public function status(Booking $booking)
    {
        $booking->load(['tickets.zone', 'session.event']);

        return response()->json([
            'data' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'total' => $booking->total,
                'tickets' => $booking->tickets,
            ]
        ]);
    }

    public function qr(Booking $booking, QrService $qrService)
    {
        $booking->load(['session.event', 'tickets.zone']);

        $qrImage = $qrService->generate($booking);

        return response()->json([
            'data' => [
                'booking_id' => $booking->id,
                'qr' => $qrImage,
                'event' => $booking->session->event->title,
                'session' => $booking->session->date . ' ' . $booking->session->time,
                'total' => $booking->total,
            ]
        ]);
    }
}