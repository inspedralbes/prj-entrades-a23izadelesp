<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AppSession;
use App\Models\OccupiedZone;
use Illuminate\Support\Facades\Redis;

class ZoneLockService
{
    public function lockZone(int $sessionId, string $zoneId, int $quantity, string $identifier): string|false
    {
        $session = AppSession::find($sessionId);

        if (!$session) {
            return false;
        }

        $config = $session->venue_config;

        if ($config['type'] !== 'zones') {
            return false;
        }

        $zone = collect($config['zones'])->firstWhere('id', $zoneId);

        if (!$zone) {
            return false;
        }

        $available = $this->calculateAvailability($sessionId, $zoneId, $zone['capacity']);

        if ($available < $quantity) {
            return false;
        }

        $lockId = uniqid('lock_', true);
        $key = "zone:lock:{$sessionId}:{$zoneId}:{$lockId}";
        $ttl = (int) config('app.soft_lock_ttl', 300);

        Redis::setex($key, $ttl, json_encode([
            'identifier' => $identifier,
            'quantity' => $quantity,
        ]));

        Redis::incrby("zone:reserved:{$sessionId}:{$zoneId}", $quantity);

        Redis::publish('zone:locked', json_encode([
            'session_id' => $sessionId,
            'zone_id' => $zoneId,
            'quantity' => $quantity,
            'lock_id' => $lockId,
        ]));

        return $lockId;
    }

    public function releaseZone(int $sessionId, string $zoneId, string $lockId, string $identifier): bool
    {
        $key = "zone:lock:{$sessionId}:{$zoneId}:{$lockId}";
        $data = Redis::get($key);

        if ($data === null) {
            return false;
        }

        $lock = json_decode($data, true);

        if ($lock['identifier'] !== $identifier) {
            return false;
        }

        $quantity = $lock['quantity'];

        Redis::del($key);
        Redis::decrby("zone:reserved:{$sessionId}:{$zoneId}", $quantity);

        Redis::publish('zone:released', json_encode([
            'session_id' => $sessionId,
            'zone_id' => $zoneId,
        ]));

        return true;
    }

    public function getZoneReservedCount(int $sessionId, string $zoneId): int
    {
        return (int) Redis::get("zone:reserved:{$sessionId}:{$zoneId}");
    }

    public function getZoneAvailability(int $sessionId): array
    {
        $session = AppSession::find($sessionId);

        if (!$session || $session->venue_config['type'] !== 'zones') {
            return [];
        }

        $availability = [];

        foreach ($session->venue_config['zones'] as $zone) {
            $availability[$zone['id']] = $this->calculateAvailability(
                $sessionId,
                $zone['id'],
                $zone['capacity']
            );
        }

        return $availability;
    }

    private function calculateAvailability(int $sessionId, string $zoneId, int $capacity): int
    {
        $reservedInRedis = $this->getZoneReservedCount($sessionId, $zoneId);

        $occupiedInDb = OccupiedZone::where('session_id', $sessionId)
            ->where('zone_id', $zoneId)
            ->sum('quantity');

        return max(0, $capacity - $occupiedInDb - $reservedInRedis);
    }
}
