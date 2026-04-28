<?php

use App\Models\AppSession;
use App\Models\Event;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    $this->event = Event::factory()->create(['type' => 'movie']);
    $this->session = AppSession::factory()->create([
        'event_id' => $this->event->id,
        'venue_config' => [
            'type' => 'grid',
            'rows' => 1,
            'cols' => 2,
            'layout' => [[1, 1]],
        ],
    ]);
});

it('only one request can lock the same seat', function () {
    $sessionId = $this->session->id;

    $response1 = $this->postJson("/api/sessions/{$sessionId}/seats/lock", [
        'row' => 0,
        'col' => 0,
        'identifier' => 'user_1',
    ]);

    $response2 = $this->postJson("/api/sessions/{$sessionId}/seats/lock", [
        'row' => 0,
        'col' => 0,
        'identifier' => 'user_2',
    ]);

    $response1->assertStatus(200)->assertJson(['locked' => true]);
    $response2->assertStatus(422);

    expect(Redis::get("seat:lock:{$sessionId}:0:0"))->toBe('user_1');
});

it('seat lock expires and another user can lock', function () {
    $sessionId = $this->session->id;

    $response = $this->postJson("/api/sessions/{$sessionId}/seats/lock", [
        'row' => 0,
        'col' => 0,
        'identifier' => 'user_1',
    ]);

    expect($response->status())->toBe(200);

    $lockKey = "seat:lock:{$sessionId}:0:0";
    Redis::expire($lockKey, 1);

    sleep(2);

    $response2 = $this->postJson("/api/sessions/{$sessionId}/seats/lock", [
        'row' => 0,
        'col' => 0,
        'identifier' => 'user_2',
    ]);

    expect($response2->status())->toBe(200);
    expect(Redis::get($lockKey))->toBe('user_2');
});

it('same user can re-lock the same seat idempotently', function () {
    $sessionId = $this->session->id;

    $this->postJson("/api/sessions/{$sessionId}/seats/lock", [
        'row' => 0,
        'col' => 1,
        'identifier' => 'same_user',
    ]);

    $response = $this->postJson("/api/sessions/{$sessionId}/seats/lock", [
        'row' => 0,
        'col' => 1,
        'identifier' => 'same_user',
    ]);

    $response->assertStatus(200)->assertJson(['locked' => true]);
    expect(Redis::get("seat:lock:{$sessionId}:0:1"))->toBe('same_user');
});