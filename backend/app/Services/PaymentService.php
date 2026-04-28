<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\OccupiedSeat;
use App\Models\OccupiedZone;
use App\Models\OccupiedZoneSeat;
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
        $booking->loadMissing('tickets');
        $processedZoneLocks = [];
        
        foreach ($booking->tickets as $ticket) {
            if ($ticket->zone_id === null && $ticket->row !== null && $ticket->col !== null) {
                OccupiedSeat::create([
                    'booking_id' => $booking->id,
                    'session_id' => $session->id,
                    'row' => $ticket->row,
                    'col' => $ticket->col,
                ]);
                
                $ticket->update(['status' => 'confirmed']);
                
                $lockKey = "seat:lock:{$session->id}:{$ticket->row}:{$ticket->col}";
                Redis::del($lockKey);
            }
            
            if ($ticket->zone_id && $ticket->row === null && $ticket->col === null) {
                $zoneId = $ticket->zone_id;
                
                OccupiedZone::create([
                    'booking_id' => $booking->id,
                    'session_id' => $session->id,
                    'zone_id' => (string) $zoneId,
                    'quantity' => 1,
                ]);
                
                $ticket->update(['status' => 'confirmed']);
                
                                if (!isset($processedZoneLocks[$zoneId])) {
                                    $processedZoneLocks[$zoneId] = true;

                                    $lockId = Redis::get("booking:zone_lock:{$booking->id}:{$zoneId}");
                    $lockKey = "zone:lock:{$session->id}:{$zoneId}:{$lockId}";
                    $lockData = Redis::get($lockKey);
                    $decoded = $lockData ? json_decode($lockData, true) : null;
                    $quantity = (int) ($decoded['quantity'] ?? 1);

                                    Redis::del($lockKey);
                    Redis::decrby("zone:reserved:{$session->id}:{$zoneId}", $quantity);
                }
            }

            if ($ticket->zone_id && $ticket->row !== null && $ticket->col !== null) {
                OccupiedZoneSeat::create([
                    'booking_id' => $booking->id,
                    'session_id' => $session->id,
                    'zone_id' => $ticket->zone_id,
                    'row' => $ticket->row,
                    'col' => $ticket->col,
                ]);

                $ticket->update(['status' => 'confirmed']);

                $lockKey = "zone-seat:lock:{$session->id}:{$ticket->zone_id}:{$ticket->row}:{$ticket->col}";
                Redis::del($lockKey);
                Redis::hdel("zone-seat:locks:{$session->id}:{$ticket->zone_id}", "{$ticket->row}:{$ticket->col}");
            }
            
            $qrCode = $this->generateQRCode($booking->id, $ticket->id);
            $ticket->update(['qr_code' => $qrCode]);
        }
        
        $booking->update(['status' => 'confirmed']);
        
        // Determinar a qué email enviar: el guest_email o el del usuario registrado
        $email = $booking->guest_email ?? $booking->user?->email;
        if ($email) {
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\BookingConfirmationMail($booking));
        }
        
        $this->publishEvent('booking:confirmed', [
            'booking_id' => $booking->id,
            'session_id' => $session->id,
            'socket_id' => \Illuminate\Support\Facades\Redis::get("booking_socket:{$booking->id}"),
        ]);
    }

    private function failBooking(Booking $booking): void
    {
        $session = $booking->session;
        $booking->loadMissing('tickets');
        $processedZoneLocks = [];
        
        foreach ($booking->tickets as $ticket) {
            if ($ticket->zone_id === null && $ticket->row !== null && $ticket->col !== null) {
                $lockKey = "seat:lock:{$session->id}:{$ticket->row}:{$ticket->col}";
                Redis::del($lockKey);
                
                $ticket->update(['status' => 'failed']);
            }
            
            if ($ticket->zone_id && $ticket->row === null && $ticket->col === null) {
                $zoneId = $ticket->zone_id;
                if (!isset($processedZoneLocks[$zoneId])) {
                    $processedZoneLocks[$zoneId] = true;

                    $lockId = Redis::get("booking:zone_lock:{$booking->id}:{$zoneId}");
                    $lockKey = "zone:lock:{$session->id}:{$zoneId}:{$lockId}";
                    $lockData = Redis::get($lockKey);
                    $decoded = $lockData ? json_decode($lockData, true) : null;
                    $quantity = (int) ($decoded['quantity'] ?? 1);

                    Redis::del($lockKey);
                    Redis::decrby("zone:reserved:{$session->id}:{$zoneId}", $quantity);
                }
                
                $ticket->update(['status' => 'failed']);
            }

            if ($ticket->zone_id && $ticket->row !== null && $ticket->col !== null) {
                $lockKey = "zone-seat:lock:{$session->id}:{$ticket->zone_id}:{$ticket->row}:{$ticket->col}";
                Redis::del($lockKey);
                Redis::hdel("zone-seat:locks:{$session->id}:{$ticket->zone_id}", "{$ticket->row}:{$ticket->col}");

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