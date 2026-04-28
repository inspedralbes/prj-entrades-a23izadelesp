<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OccupiedZone;
use App\Models\Zone;
use Illuminate\Support\Facades\Redis;

class ZoneLockService
{
    public function lockZone(int $sessionId, int|string $zoneId, int $quantity, string $identifier): string|false
    {
        $zoneNumericId = is_numeric((string) $zoneId) ? (int) $zoneId : null;
        $capacity = null;

        if ($zoneNumericId !== null) {
            $zone = Zone::query()
                ->where('session_id', $sessionId)
                ->find($zoneNumericId);

            if ($zone && $zone->zone_type !== 'general_admission') {
                return false;
            }

            if ($zone) {
                $capacity = (int) $zone->capacity;
                $zoneId = $zoneNumericId;
            }
        }

        if ($capacity === null) {
            $session = \App\Models\AppSession::find($sessionId);
            $configZone = collect($session?->venue_config['zones'] ?? [])->firstWhere('id', (string) $zoneId);
            if (!$configZone) {
                return false;
            }
            $capacity = (int) ($configZone['capacity'] ?? 0);
        }

        $available = $this->calculateAvailability($sessionId, $zoneId, $capacity);

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
            'available' => max(0, $available - $quantity),
        ]));

        return $lockId;
    }

    public function releaseZone(int $sessionId, int|string $zoneId, string $lockId, string $identifier): bool
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
            'available' => $this->getAvailabilityByZoneId($sessionId, $zoneId),
        ]));

        return true;
    }

    public function getZoneReservedCount(int $sessionId, int|string $zoneId): int
    {
        return (int) Redis::get("zone:reserved:{$sessionId}:{$zoneId}");
    }

    public function getZoneAvailability(int $sessionId): array
    {
        $zoneRows = Zone::query()
            ->where('session_id', $sessionId)
            ->where('zone_type', 'general_admission')
            ->get();

        if ($zoneRows->isNotEmpty()) {
            return $zoneRows->mapWithKeys(function (Zone $zone) use ($sessionId): array {
                return [$zone->id => $this->calculateAvailability($sessionId, $zone->id, (int) $zone->capacity)];
            })->all();
        }

        $session = \App\Models\AppSession::find($sessionId);
        $configZones = $session?->venue_config['zones'] ?? [];

        return collect($configZones)->mapWithKeys(function (array $zone) use ($sessionId): array {
            $zoneId = (string) ($zone['id'] ?? '');
            $capacity = (int) ($zone['capacity'] ?? 0);
            return [$zoneId => $this->calculateAvailability($sessionId, $zoneId, $capacity)];
        })->all();
    }

    public function getAvailabilityByZoneId(int $sessionId, int|string $zoneId): int
    {
        if (is_numeric((string) $zoneId)) {
            $zone = Zone::query()->where('session_id', $sessionId)->find((int) $zoneId);

            if ($zone) {
                return $this->calculateAvailability($sessionId, (int) $zoneId, (int) $zone->capacity);
            }
        }

        $session = \App\Models\AppSession::find($sessionId);
        $configZone = collect($session?->venue_config['zones'] ?? [])->firstWhere('id', (string) $zoneId);
        if (!$configZone) {
            return 0;
        }

        return $this->calculateAvailability($sessionId, (string) $zoneId, (int) ($configZone['capacity'] ?? 0));
    }

    private function calculateAvailability(int $sessionId, int|string $zoneId, int $capacity): int
    {
        $reservedInRedis = $this->getZoneReservedCount($sessionId, $zoneId);

        $occupiedInDb = OccupiedZone::where('session_id', $sessionId)
            ->where('zone_id', (string) $zoneId)
            ->sum('quantity');

        return max(0, $capacity - $occupiedInDb - $reservedInRedis);
    }
}
