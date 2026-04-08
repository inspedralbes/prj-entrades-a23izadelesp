<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\OccupiedSeat;
use App\Models\OccupiedZone;
use App\Models\Ticket;
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
            'seats' => 'array',
            'seats.*.row' => 'required',
            'seats.*.col' => 'required|integer',
            'zones' => 'array',
            'zones.*.zone_id' => 'exists:zones,id',
            'zones.*.quantity' => 'integer|min:1|max:10',
            'guest_email' => 'nullable|email',
        ]);

        $user = Auth::user();
        $guestEmail = $validated['guest_email'] ?? ($user?->email);

        if (!$user && !$guestEmail) {
            return response()->json(['error' => 'Email requerit per a compra guest'], 422);
        }

        $total = 0;
        $seats = $validated['seats'] ?? [];
        $zones = $validated['zones'] ?? [];

        foreach ($seats as $seat) {
            $lockKey = "seat:lock:{$validated['session_id']}:{$seat['row']}:{$seat['col']}";
            if (!Redis::exists($lockKey)) {
                return response()->json(['error' => 'Seat no bloquejat'], 422);
            }
            $total += $seat['price'] ?? 0;
        }

        foreach ($zones as $zone) {
            $lockKey = "zone:lock:{$validated['session_id']}:{$zone['zone_id']}";
            if (!Redis::exists($lockKey)) {
                return response()->json(['error' => 'Zona no bloquejada'], 422);
            }
            $total += ($zone['price'] ?? 0) * ($zone['quantity'] ?? 1);
        }

        $booking = Booking::create([
            'user_id' => $user?->id,
            'guest_email' => $guestEmail,
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

        foreach ($zones as $zone) {
            for ($i = 0; $i < ($zone['quantity'] ?? 1); $i++) {
                Ticket::create([
                    'booking_id' => $booking->id,
                    'zone_id' => $zone['zone_id'],
                ]);
            }
        }

        Redis::rpush('purchase:queue', $booking->id);

        ProcessPayment::dispatch($booking->id);

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