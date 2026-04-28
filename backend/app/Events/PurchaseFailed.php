<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $bookingId;
    public int $sessionId;
    public ?string $socketId;

    public function __construct(int $bookingId, int $sessionId, ?string $socketId)
    {
        $this->bookingId = $bookingId;
        $this->sessionId = $sessionId;
        $this->socketId = $socketId;
    }
}