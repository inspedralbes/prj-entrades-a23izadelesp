<?php

declare(strict_types=1);

use App\Models\AppSession;
use App\Models\Event;
use App\Models\OccupiedZone;
use App\Models\Booking;
use App\Services\ZoneLockService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::connection()->flushdb();
    $this->service = app(ZoneLockService::class);
    $this->session = AppSession::factory()->create([
        'event_id' => Event::factory()->create(['type' => 'concert'])->id,
        'venue_config' => [
            'type' => 'zones',
            'zones' => [
                ['id' => 'pista', 'name' => 'Pista', 'capacity' => 100, 'price' => 45.00, 'color' => '#10B981'],
                ['id' => 'vip', 'name' => 'VIP', 'capacity' => 10, 'price' => 120.00, 'color' => '#EF4444'],
            ],
        ],
    ]);
});

describe('ZoneLockService', function () {

    it('reserves tickets in a zone', function () {
        $result = $this->service->lockZone($this->session->id, 'pista', 5, 'user-1');

        expect($result)->toBeString();
        expect($this->service->getZoneReservedCount($this->session->id, 'pista'))->toBe(5);
    });

    it('accumulates reservations in same zone', function () {
        $this->service->lockZone($this->session->id, 'pista', 5, 'user-1');
        $this->service->lockZone($this->session->id, 'pista', 3, 'user-2');

        expect($this->service->getZoneReservedCount($this->session->id, 'pista'))->toBe(8);
    });

    it('returns false when exceeding zone capacity', function () {
        $this->service->lockZone($this->session->id, 'pista', 100, 'user-1');

        $result = $this->service->lockZone($this->session->id, 'pista', 1, 'user-2');

        expect($result)->toBeFalse();
    });

    it('considers DB occupied seats in availability', function () {
        $booking = Booking::factory()->create(['session_id' => $this->session->id]);
        OccupiedZone::create([
            'booking_id' => $booking->id,
            'session_id' => $this->session->id,
            'zone_id' => 'pista',
            'quantity' => 95,
        ]);

        // 95 in DB, capacity 100, only 5 available
        $result = $this->service->lockZone($this->session->id, 'pista', 5, 'user-1');
        expect($result)->not->toBeFalse();

        // Now 100 taken (95 DB + 5 Redis), should fail
        $fail = $this->service->lockZone($this->session->id, 'pista', 1, 'user-2');
        expect($fail)->toBeFalse();
    });

    it('gets zone availability', function () {
        $this->service->lockZone($this->session->id, 'pista', 20, 'user-1');

        $availability = $this->service->getZoneAvailability($this->session->id);

        expect($availability['pista'])->toBe(80);
        expect($availability['vip'])->toBe(10);
    });

    it('releases a zone reservation', function () {
        $lockId = $this->service->lockZone($this->session->id, 'pista', 5, 'user-1');

        $result = $this->service->releaseZone($this->session->id, 'pista', $lockId, 'user-1');

        expect($result)->toBeTrue();
        expect($this->service->getZoneReservedCount($this->session->id, 'pista'))->toBe(0);
    });

    it('does not release a zone lock owned by another user', function () {
        $lockId = $this->service->lockZone($this->session->id, 'pista', 5, 'user-1');

        $result = $this->service->releaseZone($this->session->id, 'pista', $lockId, 'user-2');

        expect($result)->toBeFalse();
        expect($this->service->getZoneReservedCount($this->session->id, 'pista'))->toBe(5);
    });

    it('sets TTL on zone lock', function () {
        $lockId = $this->service->lockZone($this->session->id, 'pista', 5, 'user-1');

        $key = "zone:lock:{$this->session->id}:pista:{$lockId}";
        $ttl = Redis::ttl($key);

        expect($ttl)->toBeGreaterThan(0);
    });
});

describe('ZoneLock API', function () {

    it('reserves zone tickets via API', function () {
        $response = $this->postJson("/api/sessions/{$this->session->id}/zones/lock", [
            'zone_id' => 'pista',
            'quantity' => 5,
            'identifier' => 'user-1',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['locked', 'lock_id']);
    });

    it('returns 422 when exceeding zone capacity', function () {
        $this->postJson("/api/sessions/{$this->session->id}/zones/lock", [
            'zone_id' => 'pista',
            'quantity' => 100,
            'identifier' => 'user-1',
        ]);

        $response = $this->postJson("/api/sessions/{$this->session->id}/zones/lock", [
            'zone_id' => 'pista',
            'quantity' => 1,
            'identifier' => 'user-2',
        ]);

        $response->assertStatus(422);
    });

    it('releases zone reservation via API', function () {
        $lockResponse = $this->postJson("/api/sessions/{$this->session->id}/zones/lock", [
            'zone_id' => 'pista',
            'quantity' => 5,
            'identifier' => 'user-1',
        ]);
        $lockId = $lockResponse->json('lock_id');

        $response = $this->deleteJson("/api/sessions/{$this->session->id}/zones/unlock", [
            'zone_id' => 'pista',
            'lock_id' => $lockId,
            'identifier' => 'user-1',
        ]);

        $response->assertStatus(200)
            ->assertJson(['released' => true]);
    });
});
