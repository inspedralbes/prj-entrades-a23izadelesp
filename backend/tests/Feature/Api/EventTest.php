<?php

declare(strict_types=1);

use App\Models\AppSession;
use App\Models\Event;

use function Pest\Laravel\getJson;

describe('Events API', function () {

    it('returns all events', function () {
        Event::factory()->count(3)->create();

        $response = getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    it('returns events filtered by type movie', function () {
        Event::factory()->create(['type' => 'movie']);
        Event::factory()->create(['type' => 'concert']);

        $response = getJson('/api/events?type=movie');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['type' => 'movie']);
    });

    it('returns events filtered by type concert', function () {
        Event::factory()->create(['type' => 'movie']);
        Event::factory()->create(['type' => 'concert']);

        $response = getJson('/api/events?type=concert');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['type' => 'concert']);
    });

    it('returns event detail with sessions', function () {
        $event = Event::factory()->create();
        AppSession::factory()->count(2)->create(['event_id' => $event->id]);

        $response = getJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $event->id)
            ->assertJsonCount(2, 'data.sessions');
    });

    it('returns 404 for non-existent event', function () {
        $response = getJson('/api/events/99999');

        $response->assertStatus(404);
    });

    it('returns event with correct fields', function () {
        $event = Event::factory()->create([
            'title' => 'Test Movie',
            'type' => 'movie',
            'description' => 'A test description',
        ]);

        $response = getJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Test Movie')
            ->assertJsonPath('data.type', 'movie')
            ->assertJsonPath('data.description', 'A test description');
    });
});
