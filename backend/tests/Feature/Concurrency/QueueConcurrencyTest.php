<?php

use App\Models\Event;
use App\Models\AppSession;
use Illuminate\Support\Facades\Redis;
use function Pest\Faker\fake;

beforeEach(function () {
    $this->event = Event::factory()->create();
    $this->session = AppSession::factory()->create(['event_id' => $this->event->id]);
});

it('queue maintains FIFO order under load', function () {
    $sessionId = $this->session->id;
    $userCount = 50;

    for ($i = 1; $i <= $userCount; $i++) {
        $response = $this->postJson("/api/sessions/{$sessionId}/queue/join", [
            'identifier' => "user_{$i}",
        ]);

        $response->assertOk();
        $response->assertJsonPath('position', $i);
    }

    $queue = Redis::lrange("queue:{$sessionId}", 0, -1);

    expect(count($queue))->toBe($userCount);

    for ($i = 0; $i < $userCount; $i++) {
        expect($queue[$i])->toBe('user_'.($i + 1));
    }

    Redis::del("queue:{$sessionId}");
    Redis::del("queue:{$sessionId}:active");
});

it('admit processes in correct order', function () {
    $sessionId = $this->session->id;
    $userCount = 20;
    
    for ($i = 1; $i <= $userCount; $i++) {
        $response = $this->postJson("/api/sessions/{$sessionId}/queue/join", [
            'identifier' => "user_{$i}",
        ]);

        $response->assertOk();
    }

    $response = $this->postJson("/api/sessions/{$sessionId}/queue/admit", [
        'batch_size' => 5,
    ]);

    $response->assertOk();
    $response->assertJsonCount(5, 'released');

    $queue = Redis::lrange("queue:{$sessionId}", 0, -1);

    expect(count($queue))->toBe($userCount - 5);
    expect($queue[0])->toBe('user_6');

    Redis::del("queue:{$sessionId}");
    Redis::del("queue:{$sessionId}:active");
});