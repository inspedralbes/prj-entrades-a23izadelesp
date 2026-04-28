<?php

declare(strict_types=1);

use App\Models\AppSession;
use App\Models\Event;
use App\Services\SeatLockService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::connection()->flushdb();
    $this->service = app(SeatLockService::class);
    $this->session = AppSession::factory()->create([
        'event_id' => Event::factory()->create(['type' => 'movie'])->id,
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
});

describe('SeatLockService', function () {

    it('locks a seat with identifier', function () {
        $result = $this->service->lockSeat($this->session->id, 0, 0, 'user-1');

        expect($result)->toBeTrue();
        expect(Redis::get("seat:lock:{$this->session->id}:0:0"))->toBe('user-1');
    });

    it('returns false when locking an already locked seat', function () {
        $this->service->lockSeat($this->session->id, 0, 0, 'user-1');

        $result = $this->service->lockSeat($this->session->id, 0, 0, 'user-2');

        expect($result)->toBeFalse();
    });

    it('allows same user to re-lock same seat', function () {
        $this->service->lockSeat($this->session->id, 0, 0, 'user-1');

        $result = $this->service->lockSeat($this->session->id, 0, 0, 'user-1');

        expect($result)->toBeTrue();
    });

    it('checks if a seat is locked', function () {
        expect($this->service->isSeatLocked($this->session->id, 0, 0))->toBeFalse();

        $this->service->lockSeat($this->session->id, 0, 0, 'user-1');

        expect($this->service->isSeatLocked($this->session->id, 0, 0))->toBeTrue();
    });

    it('releases a seat', function () {
        $this->service->lockSeat($this->session->id, 0, 0, 'user-1');

        $result = $this->service->releaseSeat($this->session->id, 0, 0, 'user-1');

        expect($result)->toBeTrue();
        expect($this->service->isSeatLocked($this->session->id, 0, 0))->toBeFalse();
    });

    it('does not release a seat locked by another user', function () {
        $this->service->lockSeat($this->session->id, 0, 0, 'user-1');

        $result = $this->service->releaseSeat($this->session->id, 0, 0, 'user-2');

        expect($result)->toBeFalse();
        expect($this->service->isSeatLocked($this->session->id, 0, 0))->toBeTrue();
    });

    it('gets all seat locks for a session', function () {
        $this->service->lockSeat($this->session->id, 0, 0, 'user-1');
        $this->service->lockSeat($this->session->id, 1, 1, 'user-2');

        $locks = $this->service->getSeatLocks($this->session->id);

        expect($locks)->toHaveCount(2);
        expect($locks)->toHaveKey('0:0');
        expect($locks)->toHaveKey('1:1');
    });

    it('sets TTL on seat lock', function () {
        config(['app.soft_lock_ttl' => 2]);
        $this->service->lockSeat($this->session->id, 0, 0, 'user-1');

        $ttl = Redis::ttl("seat:lock:{$this->session->id}:0:0");

        expect($ttl)->toBeGreaterThan(0);
        expect($ttl)->toBeLessThanOrEqual(2);
    });
});

describe('SeatLock API', function () {

    it('locks a seat via API', function () {
        $response = $this->postJson("/api/sessions/{$this->session->id}/seats/lock", [
            'row' => 0,
            'col' => 0,
            'identifier' => 'user-1',
        ]);

        $response->assertStatus(200)
            ->assertJson(['locked' => true]);
    });

    it('returns 422 when seat is already locked', function () {
        $this->postJson("/api/sessions/{$this->session->id}/seats/lock", [
            'row' => 0,
            'col' => 0,
            'identifier' => 'user-1',
        ]);

        $response = $this->postJson("/api/sessions/{$this->session->id}/seats/lock", [
            'row' => 0,
            'col' => 0,
            'identifier' => 'user-2',
        ]);

        $response->assertStatus(422);
    });

    it('returns 422 when seat does not exist in layout', function () {
        $response = $this->postJson("/api/sessions/{$this->session->id}/seats/lock", [
            'row' => 1,
            'col' => 2,
            'identifier' => 'user-1',
        ]);

        $response->assertStatus(422);
    });

    it('unlocks a seat via API', function () {
        $this->postJson("/api/sessions/{$this->session->id}/seats/lock", [
            'row' => 0,
            'col' => 0,
            'identifier' => 'user-1',
        ]);

        $response = $this->deleteJson("/api/sessions/{$this->session->id}/seats/unlock", [
            'row' => 0,
            'col' => 0,
            'identifier' => 'user-1',
        ]);

        $response->assertStatus(200)
            ->assertJson(['released' => true]);
    });

    it('returns 422 when unlocking a seat not locked by user', function () {
        $response = $this->deleteJson("/api/sessions/{$this->session->id}/seats/unlock", [
            'row' => 0,
            'col' => 0,
            'identifier' => 'user-1',
        ]);

        $response->assertStatus(422);
    });
});
