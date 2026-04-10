<?php

use App\Models\AppSession;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    $this->event = Event::factory()->create(['type' => 'movie']);
    $this->session = AppSession::factory()->create([
        'event_id' => $this->event->id,
        'price' => 10,
        'venue_config' => [
            'type' => 'grid',
            'rows' => 1,
            'cols' => 3,
            'layout' => [[1, 1, 1]],
        ],
    ]);
});

it('creates independent bookings for different locked seats', function () {
    $sessionId = $this->session->id;

    $bookingIds = [];
    for ($col = 0; $col < 3; $col++) {
        $identifier = "user_{$col}";
        Redis::setex("seat:lock:{$sessionId}:0:{$col}", 300, $identifier);

        $this->postJson('/api/bookings', [
            'session_id' => $sessionId,
            'identifier' => $identifier,
            'seats' => [
                ['row' => 0, 'col' => $col],
            ],
            'guest_email' => "user{$col}@example.com",
        ])->assertStatus(201)->assertJsonStructure(['data' => ['id', 'status', 'total']]);

        $bookingIds[] = Booking::query()->latest('id')->value('id');
    }

    expect($bookingIds)->toHaveCount(3);
    expect(array_unique($bookingIds))->toHaveCount(3);
    expect(Booking::query()->count())->toBe(3);
});

it('booking cannot be processed twice', function () {
    $sessionId = $this->session->id;
    $row = 0;
    $col = 0;

    Redis::setex("seat:lock:{$sessionId}:{$row}:{$col}", 300, 'user_1');

    $response = $this->postJson('/api/bookings', [
        'session_id' => $sessionId,
        'identifier' => 'user_1',
        'seats' => [
            ['row' => $row, 'col' => $col],
        ],
        'guest_email' => 'test@example.com',
    ]);

    $response->assertStatus(201);
    $bookingId = $response->json('data.id');

    $booking1 = Booking::find($bookingId);
    expect($booking1)->not->toBeNull();

    $response2 = $this->postJson('/api/bookings', [
        'session_id' => $sessionId,
        'identifier' => 'user_2',
        'seats' => [
            ['row' => $row, 'col' => $col],
        ],
        'guest_email' => 'test2@example.com',
    ]);

    expect($response2->status())->toBe(422);
});

it('rejects booking when seat is not locked by requester', function () {
    $sessionId = $this->session->id;
    $row = 0;
    $col = 1;

    Redis::setex("seat:lock:{$sessionId}:{$row}:{$col}", 300, 'owner_user');

    $response = $this->postJson('/api/bookings', [
        'session_id' => $sessionId,
        'identifier' => 'another_user',
        'seats' => [
            ['row' => $row, 'col' => $col],
        ],
        'guest_email' => 'fail@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure(['error', 'unavailable_seats']);
});