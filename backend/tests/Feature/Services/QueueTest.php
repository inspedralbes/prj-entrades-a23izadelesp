<?php

declare(strict_types=1);

use App\Models\AppSession;
use App\Models\Event;
use App\Services\QueueService;
use Illuminate\Support\Facades\Redis;

use function Pest\Laravel\{postJson, getJson};

beforeEach(function () {
    Redis::connection()->flushdb();
    $this->service = app(QueueService::class);
    $this->session = AppSession::factory()->create([
        'event_id' => Event::factory()->create(['type' => 'concert'])->id,
        'venue_config' => [
            'type' => 'zones',
            'zones' => [
                ['id' => 'pista', 'name' => 'Pista', 'capacity' => 500, 'price' => 45.00, 'color' => '#10B981'],
            ],
        ],
    ]);
});

describe('QueueService', function () {

    it('adds user to queue and returns position', function () {
        $position = $this->service->join($this->session->id, 'user-1');

        expect($position)->toBe(1);
    });

    it('maintains FIFO order', function () {
        $this->service->join($this->session->id, 'user-1');
        $this->service->join($this->session->id, 'user-2');
        $this->service->join($this->session->id, 'user-3');

        expect($this->service->getPosition($this->session->id, 'user-1'))->toBe(1);
        expect($this->service->getPosition($this->session->id, 'user-2'))->toBe(2);
        expect($this->service->getPosition($this->session->id, 'user-3'))->toBe(3);
    });

    it('returns existing position if user already in queue', function () {
        $this->service->join($this->session->id, 'user-1');
        $this->service->join($this->session->id, 'user-2');

        $position = $this->service->join($this->session->id, 'user-1');

        expect($position)->toBe(1);
    });

    it('releases batch of users to active set', function () {
        $this->service->join($this->session->id, 'user-1');
        $this->service->join($this->session->id, 'user-2');
        $this->service->join($this->session->id, 'user-3');
        $this->service->join($this->session->id, 'user-4');

        $released = $this->service->releaseBatch($this->session->id, 2);

        expect($released)->toHaveCount(2);
        expect($released)->toContain('user-1');
        expect($released)->toContain('user-2');
    });

    it('removes released users from queue', function () {
        $this->service->join($this->session->id, 'user-1');
        $this->service->join($this->session->id, 'user-2');
        $this->service->join($this->session->id, 'user-3');

        $this->service->releaseBatch($this->session->id, 2);

        expect($this->service->getPosition($this->session->id, 'user-1'))->toBeNull();
        expect($this->service->getPosition($this->session->id, 'user-2'))->toBeNull();
        expect($this->service->getPosition($this->session->id, 'user-3'))->toBe(1);
    });

    it('checks if user is active', function () {
        $this->service->join($this->session->id, 'user-1');

        expect($this->service->isActive($this->session->id, 'user-1'))->toBeFalse();

        $this->service->releaseBatch($this->session->id, 1);

        expect($this->service->isActive($this->session->id, 'user-1'))->toBeTrue();
    });

    it('does not add active user back to queue', function () {
        $this->service->join($this->session->id, 'user-1');
        $this->service->releaseBatch($this->session->id, 1);

        $result = $this->service->join($this->session->id, 'user-1');

        expect($result)->toBe(0);
        expect($this->service->isActive($this->session->id, 'user-1'))->toBeTrue();
    });

    it('returns null position for user not in queue', function () {
        expect($this->service->getPosition($this->session->id, 'user-unknown'))->toBeNull();
    });

    it('returns empty array when releasing from empty queue', function () {
        $released = $this->service->releaseBatch($this->session->id, 5);

        expect($released)->toBeEmpty();
    });

    it('returns available count less than batch size when queue is smaller', function () {
        $this->service->join($this->session->id, 'user-1');

        $released = $this->service->releaseBatch($this->session->id, 10);

        expect($released)->toHaveCount(1);
    });
});

describe('Queue API', function () {

    it('joins queue via API', function () {
        $response = $this->postJson("/api/sessions/{$this->session->id}/queue/join", [
            'identifier' => 'user-1',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('position', 1);
    });

    it('returns current position via API', function () {
        $this->postJson("/api/sessions/{$this->session->id}/queue/join", [
            'identifier' => 'user-1',
        ]);
        $this->postJson("/api/sessions/{$this->session->id}/queue/join", [
            'identifier' => 'user-2',
        ]);

        $response = $this->getJson("/api/sessions/{$this->session->id}/queue/position?identifier=user-2");

        $response->assertStatus(200)
            ->assertJsonPath('position', 2);
    });

    it('admits batch via API', function () {
        $this->postJson("/api/sessions/{$this->session->id}/queue/join", [
            'identifier' => 'user-1',
        ]);
        $this->postJson("/api/sessions/{$this->session->id}/queue/join", [
            'identifier' => 'user-2',
        ]);
        $this->postJson("/api/sessions/{$this->session->id}/queue/join", [
            'identifier' => 'user-3',
        ]);

        $response = $this->postJson("/api/sessions/{$this->session->id}/queue/admit", [
            'batch_size' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'released');
    });

    it('returns 422 when identifier is missing on join', function () {
        $response = $this->postJson("/api/sessions/{$this->session->id}/queue/join", []);

        $response->assertStatus(422);
    });
});
