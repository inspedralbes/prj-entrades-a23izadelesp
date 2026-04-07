<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\OccupiedSeat;
use App\Models\OccupiedZone;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class PaymentService
{
    private const SUCCESS_RATE = 0.9;
    private const MIN_DELAY = 2;
    private const MAX_DELAY = 5;

    public function process(Booking $booking): bool
    {
        $success = $this->simulatePayment();
        
        if ($success) {
            $this->confirmBooking($booking);
        } else {
            $this->failBooking($booking);
        }
        
        return $success;
    }

    private function simulatePayment(): bool
    {
        $delay = random_int(self::MIN_DELAY, self::MAX_DELAY);
        sleep($delay);
        
        return random_int(1, 100) <= (self::SUCCESS_RATE * 100);
    }

    private function confirmBooking(Booking $booking): void
    {
        $session = $booking->session;
        $event = $session->event;
        
        foreach ($booking->tickets as $ticket) {
            if ($ticket->seat_id) {
                $seatId = $ticket->seat_id;
                
                OccupiedSeat::create([
                    'booking_id' => $booking->id,
                    'session_id' => $session->id,
                    'seat_id' => $seatId,
                ]);
                
                $ticket->update(['status' => 'confirmed']);
                
                $lockKey = "seat_lock:{$session->id}:{$seatId}";
                Redis::del($lockKey);
            }
            
            if ($ticket->zone_id) {
                $zoneId = $ticket->zone_id;
                
                OccupiedZone::create([
                    'booking_id' => $booking->id,
                    'session_id' => $session->id,
                    'zone_id' => $zoneId,
                ]);
                
                $ticket->update(['status' => 'confirmed']);
                
                $lockKey = "zone_lock:{$session->id}:{$zoneId}";
                Redis::del($lockKey);
            }
            
            $qrCode = $this->generateQRCode($booking->id, $ticket->id);
            $ticket->update(['qr_code' => $qrCode]);
        }
        
        $booking->update(['status' => 'confirmed']);
        
        $this->publishEvent('booking:confirmed', [
            'booking_id' => $booking->id,
            'session_id' => $session->id,
            'socket_id' => Redis::get("booking_socket:{$booking->id}"),
        ]);
    }

    private function failBooking(Booking $booking): void
    {
        $session = $booking->session;
        
        foreach ($booking->tickets as $ticket) {
            if ($ticket->seat_id) {
                $seatId = $ticket->seat_id;
                $lockKey = "seat_lock:{$session->id}:{$seatId}";
                Redis::del($lockKey);
                
                $ticket->update(['status' => 'failed']);
            }
            
            if ($ticket->zone_id) {
                $zoneId = $ticket->zone_id;
                $lockKey = "zone_lock:{$session->id}:{$zoneId}";
                Redis::del($lockKey);
                
                $ticket->update(['status' => 'failed']);
            }
        }
        
        $booking->update(['status' => 'failed']);
        
        $this->publishEvent('booking:failed', [
            'booking_id' => $booking->id,
            'session_id' => $session->id,
            'socket_id' => Redis::get("booking_socket:{$booking->id}"),
        ]);
    }

    private function generateQRCode(int $bookingId, int $ticketId): string
    {
        $token = Str::random(32);
        return "https://queuely.app/ticket/{$bookingId}/{$ticketId}/{$token}";
    }

    private function publishEvent(string $event, array $data): void
    {
        Redis::publish($event, json_encode($data));
    }
}