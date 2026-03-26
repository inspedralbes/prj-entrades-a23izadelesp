<?php

declare(strict_types=1);

use App\Models\AppSession;
use App\Models\Booking;
use App\Models\Event;
use App\Models\OccupiedSeat;
use App\Models\OccupiedZone;

use function Pest\Laravel\getJson;

describe('Sessions API', function () {

    it('returns session detail for movie (grid)', function () {
        $event = Event::factory()->create(['type' => 'movie']);
        $session = AppSession::factory()->create([
            'event_id' => $event->id,
            'price' => 9.50,
            'venue_config' => [
                'type' => 'grid',
                'rows' => 3,
                'cols' => 4,
                'layout' => [
                    [1, 1, 1, 1],
                    [1, 1, 0, 1],
                    [1, 1, 1, 1],
                ],
            ],
        ]);

        $response = getJson("/api/sessions/{$session->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $session->id)
            ->assertJsonPath('data.price', '9.50')
            ->assertJsonPath('data.venue_config.type', 'grid')
            ->assertJsonPath('data.event.id', $event->id);
    });

    it('returns session detail for concert (zones)', function () {
        $event = Event::factory()->create(['type' => 'concert']);
        $session = AppSession::factory()->create([
            'event_id' => $event->id,
            'price' => null,
            'venue_config' => [
                'type' => 'zones',
                'zones' => [
                    ['id' => 'pista', 'name' => 'Pista', 'capacity' => 500, 'price' => 45.00, 'color' => '#10B981'],
                    ['id' => 'vip', 'name' => 'VIP', 'capacity' => 50, 'price' => 120.00, 'color' => '#EF4444'],
                ],
            ],
        ]);

        $response = getJson("/api/sessions/{$session->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.venue_config.type', 'zones')
            ->assertJsonCount(2, 'data.venue_config.zones');
    });

    it('returns 404 for non-existent session', function () {
        $response = getJson('/api/sessions/99999');

        $response->assertStatus(404);
    });

    it('returns seats state for movie session', function () {
        $event = Event::factory()->create(['type' => 'movie']);
        $session = AppSession::factory()->create([
            'event_id' => $event->id,
            'venue_config' => [
                'type' => 'grid',
                'rows' => 2,
                'cols' => 3,
                'layout' => [
                    [1, 1, 1],
                    [1, 0, 1],
                ],
            ],
        ]);

        $response = getJson("/api/sessions/{$session->id}/seats");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'grid',
                ],
            ])
            ->assertJsonPath('data.type', 'grid');
    });

    it('returns seat grid with correct dimensions', function () {
        $event = Event::factory()->create(['type' => 'movie']);
        $session = AppSession::factory()->create([
            'event_id' => $event->id,
            'venue_config' => [
                'type' => 'grid',
                'rows' => 2,
                'cols' => 3,
                'layout' => [
                    [1, 1, 1],
                    [1, 0, 1],
                ],
            ],
        ]);

        $response = getJson("/api/sessions/{$session->id}/seats");

        $response->assertStatus(200);
        $grid = $response->json('data.grid');

        expect($grid)->toHaveCount(2);
        expect($grid[0])->toHaveCount(3);
    });

    it('returns seat state as free (1) for available seats', function () {
        $event = Event::factory()->create(['type' => 'movie']);
        $session = AppSession::factory()->create([
            'event_id' => $event->id,
            'venue_config' => [
                'type' => 'grid',
                'rows' => 1,
                'cols' => 2,
                'layout' => [[1, 1]],
            ],
        ]);

        $response = getJson("/api/sessions/{$session->id}/seats");

        $response->assertStatus(200);
        $grid = $response->json('data.grid');

        expect($grid[0][0]['status'])->toBe('free');
        expect($grid[0][1]['status'])->toBe('free');
    });

    it('returns seat state as occupied for booked seats', function () {
        $event = Event::factory()->create(['type' => 'movie']);
        $session = AppSession::factory()->create([
            'event_id' => $event->id,
            'venue_config' => [
                'type' => 'grid',
                'rows' => 1,
                'cols' => 2,
                'layout' => [[1, 1]],
            ],
        ]);

        $booking = Booking::factory()->create(['session_id' => $session->id]);
        OccupiedSeat::create([
            'booking_id' => $booking->id,
            'session_id' => $session->id,
            'row' => 0,
            'col' => 1,
        ]);

        $response = getJson("/api/sessions/{$session->id}/seats");

        $response->assertStatus(200);
        $grid = $response->json('data.grid');

        expect($grid[0][0]['status'])->toBe('free');
        expect($grid[0][1]['status'])->toBe('occupied');
    });

    it('returns non-existent seats as null in grid', function () {
        $event = Event::factory()->create(['type' => 'movie']);
        $session = AppSession::factory()->create([
            'event_id' => $event->id,
            'venue_config' => [
                'type' => 'grid',
                'rows' => 1,
                'cols' => 3,
                'layout' => [[1, 0, 1]],
            ],
        ]);

        $response = getJson("/api/sessions/{$session->id}/seats");

        $response->assertStatus(200);
        $grid = $response->json('data.grid');

        expect($grid[0][0])->not->toBeNull();
        expect($grid[0][1])->toBeNull();
        expect($grid[0][2])->not->toBeNull();
    });

    it('returns zones with availability for concert session', function () {
        $event = Event::factory()->create(['type' => 'concert']);
        $session = AppSession::factory()->create([
            'event_id' => $event->id,
            'venue_config' => [
                'type' => 'zones',
                'zones' => [
                    ['id' => 'pista', 'name' => 'Pista', 'capacity' => 500, 'price' => 45.00, 'color' => '#10B981'],
                    ['id' => 'vip', 'name' => 'VIP', 'capacity' => 50, 'price' => 120.00, 'color' => '#EF4444'],
                ],
            ],
        ]);

        $response = getJson("/api/sessions/{$session->id}/seats");

        $response->assertStatus(200)
            ->assertJsonPath('data.type', 'zones')
            ->assertJsonCount(2, 'data.zones');
    });

    it('returns zones with correct availability calculation', function () {
        $event = Event::factory()->create(['type' => 'concert']);
        $session = AppSession::factory()->create([
            'event_id' => $event->id,
            'venue_config' => [
                'type' => 'zones',
                'zones' => [
                    ['id' => 'pista', 'name' => 'Pista', 'capacity' => 500, 'price' => 45.00, 'color' => '#10B981'],
                ],
            ],
        ]);

        // 50 tickets already occupied
        $booking = Booking::factory()->create(['session_id' => $session->id]);
        OccupiedZone::create([
            'booking_id' => $booking->id,
            'session_id' => $session->id,
            'zone_id' => 'pista',
            'quantity' => 50,
        ]);

        $response = getJson("/api/sessions/{$session->id}/seats");

        $response->assertStatus(200);
        $zones = $response->json('data.zones');

        expect($zones[0]['available'])->toBe(450);
        expect($zones[0]['capacity'])->toBe(500);
    });
});
