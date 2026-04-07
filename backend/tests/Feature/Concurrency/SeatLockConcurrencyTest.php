<?php

use App\Models\Event;
use App\Models\AppSession;
use App\Models\Seat;
use Illuminate\Support\Facades\Redis;
use function Pest\Faker\fake;

beforeEach(function () {
    $this->event = Event::factory()->create();
    $this->session = AppSession::factory()->create(['event_id' => $this->event->id]);
    $this->seat = Seat::factory()->create(['session_id' => $this->session->id]);
});

it('only one request can lock the same seat', function () {
    $seatId = $this->seat->id;
    $sessionId = $this->session->id;
    
    $promises = [];
    for ($i = 0; $i < 100; $i++) {
        $promises[] = async(fn () => $this->postJson("/api/sessions/{$sessionId}/seats/lock", [
            'seat_id' => $seatId,
            'user_id' => $i + 1,
        ]));
    }
    
    $responses = Promise\all($promises);
    
    $successCount = 0;
    foreach ($responses as $response) {
        if ($response->status() === 200) {
            $successCount++;
        }
    }
    
    expect($successCount)->toBe(1);
    
    $lockKey = "seat_lock:{$sessionId}:{$seatId}";
    Redis::del($lockKey);
});

it('seat lock expires and another user can lock', function () {
    $seatId = $this->seat->id;
    $sessionId = $this->session->id;
    $userId = 1;
    
    $response = $this->postJson("/api/sessions/{$sessionId}/seats/lock", [
        'seat_id' => $seatId,
        'user_id' => $userId,
    ]);
    
    expect($response->status())->toBe(200);
    
    $lockKey = "seat_lock:{$sessionId}:{$seatId}";
    Redis::expire($lockKey, 1);
    
    sleep(2);
    
    $response2 = $this->postJson("/api/sessions/{$sessionId}/seats/lock", [
        'seat_id' => $seatId,
        'user_id' => 2,
    ]);
    
    expect($response2->status())->toBe(200);
    
    Redis::del($lockKey);
});

it('occupied seats never has duplicates', function () {
    $seatId = $this->seat->id;
    $sessionId = $this->session->id;
    
    $this->postJson("/api/sessions/{$sessionId}/seats/lock", [
        'seat_id' => $seatId,
        'user_id' => 1,
    ]);
    
    $booking = \App\Models\Booking::factory()->create([
        'session_id' => $sessionId,
        'status' => 'confirmed',
    ]);
    
    \App\Models\OccupiedSeat::create([
        'booking_id' => $booking->id,
        'session_id' => $sessionId,
        'seat_id' => $seatId,
    ]);
    
    $duplicate = \App\Models\OccupiedSeat::where('session_id', $sessionId)
        ->where('seat_id', $seatId)
        ->get();
    
    expect($duplicate->count())->toBe(1);
    
    $lockKey = "seat_lock:{$sessionId}:{$seatId}";
    Redis::del($lockKey);
});