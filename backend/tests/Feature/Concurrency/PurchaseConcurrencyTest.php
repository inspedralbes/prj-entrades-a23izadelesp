<?php

use App\Models\Event;
use App\Models\AppSession;
use App\Models\Seat;
use App\Models\Booking;
use Illuminate\Support\Facades\Redis;
use function Pest\Faker\fake;

beforeEach(function () {
    $this->event = Event::factory()->create();
    $this->session = AppSession::factory()->create(['event_id' => $this->event->id]);
    $this->seat = Seat::factory()->create(['session_id' => $this->session->id]);
});

it('purchase queue processes sequentially without duplicates', function () {
    $sessionId = $this->session->id;
    $seatId = $this->seat->id;
    $bookingCount = 10;
    
    for ($i = 1; $i <= $bookingCount; $i++) {
        Redis::set("seat_lock:{$sessionId}:{$seatId}:{$i}", $i, 'EX', 300);
        
        $this->postJson('/api/bookings', [
            'session_id' => $sessionId,
            'seats' => [
                ['seat_id' => $seatId, 'price' => 10],
            ],
            'guest_email' => "user{$i}@example.com",
        ]);
    }
    
    $queue = Redis::lrange('purchase:queue', 0, -1);
    
    expect(count($queue))->toBe($bookingCount);
    
    foreach ($queue as $bookingId) {
        $isUnique = !Redis::sismember('processing_bookings', $bookingId);
        expect($isUnique)->toBeTrue();
    }
    
    Redis::del('purchase:queue');
});

it('booking cannot be processed twice', function () {
    $sessionId = $this->session->id;
    $seatId = $this->seat->id;
    
    Redis::set("seat_lock:{$sessionId}:{$seatId}", 1, 'EX', 300);
    
    $response = $this->postJson('/api/bookings', [
        'session_id' => $sessionId,
        'seats' => [
            ['seat_id' => $seatId, 'price' => 10],
        ],
        'guest_email' => 'test@example.com',
    ]);
    
    $bookingId = $response->json('data.id');
    
    $booking1 = Booking::find($bookingId);
    expect($booking1)->not->toBeNull();
    
    $response2 = $this->postJson('/api/bookings', [
        'session_id' => $sessionId,
        'seats' => [
            ['seat_id' => $seatId, 'price' => 10],
        ],
        'guest_email' => 'test2@example.com',
    ]);
    
    expect($response2->status())->toBe(422);
    
    Redis::del("seat_lock:{$sessionId}:{$seatId}");
});

it('failed payment releases seat lock', function () {
    $sessionId = $this->session->id;
    $seatId = $this->seat->id;
    
    Redis::set("seat_lock:{$sessionId}:{$seatId}", 1, 'EX', 300);
    
    $response = $this->postJson('/api/bookings', [
        'session_id' => $sessionId,
        'seats' => [
            ['seat_id' => $seatId, 'price' => 10],
        ],
        'guest_email' => 'fail@example.com',
    ]);
    
    $bookingId = $response->json('data.id');
    
    sleep(6);
    
    $booking = Booking::find($bookingId);
    
    if ($booking && $booking->status === 'failed') {
        $lockExists = Redis::exists("seat_lock:{$sessionId}:{$seatId}");
        expect($lockExists)->toBe(0);
    }
});