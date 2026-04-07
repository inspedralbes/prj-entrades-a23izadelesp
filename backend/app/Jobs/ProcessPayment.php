<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class ProcessPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $bookingId;

    public function __construct(int $bookingId)
    {
        $this->bookingId = $bookingId;
    }

    public function handle(PaymentService $paymentService): void
    {
        $booking = Booking::find($this->bookingId);
        
        if (!$booking || $booking->status !== 'pending') {
            return;
        }

        Redis::publish('purchase:processing', json_encode([
            'booking_id' => $booking->id,
            'session_id' => $booking->session_id,
            'socket_id' => Redis::get("booking_socket:{$booking->id}"),
        ]));

        $paymentService->process($booking);
    }
}