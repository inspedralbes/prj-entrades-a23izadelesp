<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function tickets()
    {
        $user = Auth::user();
        
        $bookings = Booking::where('user_id', $user->id)
            ->with(['event', 'session', 'tickets'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'data' => $bookings->map(fn ($booking) => [
                'id' => $booking->id,
                'event' => [
                    'id' => $booking->event->id,
                    'title' => $booking->event->title,
                    'image' => $booking->event->image,
                    'venue' => $booking->event->venue,
                ],
                'session' => [
                    'id' => $booking->session->id,
                    'date' => $booking->session->date,
                    'time' => $booking->session->time,
                ],
                'status' => $booking->status,
                'total' => $booking->total,
                'ticket_count' => $booking->tickets->count(),
                'created_at' => $booking->created_at->toIso8601String(),
            ])
        ]);
    }

    public function ticket(Booking $booking)
    {
        $user = Auth::user();
        
        if ($booking->user_id !== $user->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $booking->load(['event', 'session', 'tickets', 'tickets.seat', 'tickets.zone']);
        
        return response()->json([
            'data' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'total' => $booking->total,
                'event' => [
                    'id' => $booking->event->id,
                    'title' => $booking->event->title,
                    'image' => $booking->event->image,
                    'venue' => $booking->event->venue,
                ],
                'session' => [
                    'id' => $booking->session->id,
                    'date' => $booking->session->date,
                    'time' => $booking->session->time,
                ],
                'tickets' => $booking->tickets->map(fn ($ticket) => [
                    'id' => $ticket->id,
                    'seat' => $ticket->seat ? [
                        'row' => $ticket->seat->row,
                        'number' => $ticket->seat->number,
                    ] : null,
                    'zone' => $ticket->zone ? [
                        'name' => $ticket->zone->name,
                    ] : null,
                    'qr_code' => $ticket->qr_code,
                ]),
                'created_at' => $booking->created_at->toIso8601String(),
            ]
        ]);
    }
}