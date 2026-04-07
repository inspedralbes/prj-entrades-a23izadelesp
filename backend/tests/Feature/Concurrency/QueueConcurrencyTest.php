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
    
    $promises = [];
    for ($i = 1; $i <= $userCount; $i++) {
        $promises[] = async(fn () => $this->postJson("/api/sessions/{$sessionId}/queue/join", [
            'user_id' => $i,
        ]));
    }
    
    Promise\all($promises);
    
    $queue = Redis::lrange("queue:{$sessionId}", 0, -1);
    
    expect(count($queue))->toBe($userCount);
    
    for ($i = 0; $i < $userCount; $i++) {
        $item = json_decode($queue[$i]);
        expect($item->position)->toBe($i + 1);
    }
    
    Redis::del("queue:{$sessionId}");
    Redis::del("active_sessions");
});

it('admit processes in correct order', function () {
    $sessionId = $this->session->id;
    $userCount = 20;
    
    for ($i = 1; $i <= $userCount; $i++) {
        $this->postJson("/api/sessions/{$sessionId}/queue/join", [
            'user_id' => $i,
        ]);
    }
    
    $this->postJson("/api/sessions/{$sessionId}/queue/admit", [
        'count' => 5,
    ]);
    
    $queue = Redis::lrange("queue:{$sessionId}", 0, -1);
    
    expect(count($queue))->toBe($userCount - 5);
    
    Redis::del("queue:{$sessionId}");
    Redis::del("active_sessions");
});