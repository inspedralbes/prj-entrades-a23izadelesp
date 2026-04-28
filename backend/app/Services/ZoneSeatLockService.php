<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OccupiedZoneSeat;
use App\Models\Zone;
use Illuminate\Support\Facades\Redis;

class ZoneSeatLockService
{
    public function lockSeat(int $sessionId, Zone $zone, int $row, int $col, string $identifier): string|false
    {
        if ((string) $zone->session_id !== (string) $sessionId || $zone->zone_type !== 'seated') {
            return false;
        }

        if (!$this->seatExists($zone, $row, $col)) {
            return false;
        }

        $alreadyOccupied = OccupiedZoneSeat::query()
            ->where('session_id', $sessionId)
            ->where('zone_id', $zone->id)
            ->where('row', $row)
            ->where('col', $col)
            ->exists();

        if ($alreadyOccupied) {
            return false;
        }

        if ($this->isSeatLocked($sessionId, $zone->id, $row, $col)) {
            return false;
        }

        $lockId = uniqid('zseat_', true);
        $ttl = (int) config('app.soft_lock_ttl', 300);
        $key = $this->lockKey($sessionId, $zone->id, $row, $col);

        Redis::setex($key, $ttl, json_encode([
            'identifier' => $identifier,
            'lock_id' => $lockId,
            'row' => $row,
            'col' => $col,
        ]));

        Redis::hset($this->zoneLocksMap($sessionId, $zone->id), "{$row}:{$col}", $identifier);
        Redis::expire($this->zoneLocksMap($sessionId, $zone->id), $ttl);

        Redis::publish('zone-seat:locked', json_encode([
            'session_id' => $sessionId,
            'zone_id' => $zone->id,
            'row' => $row,
            'col' => $col,
            'identifier' => $identifier,
            'lock_id' => $lockId,
        ]));

        return $lockId;
    }

    public function releaseSeat(int $sessionId, Zone $zone, int $row, int $col, string $identifier): bool
    {
        $key = $this->lockKey($sessionId, $zone->id, $row, $col);
        $data = Redis::get($key);

        if ($data === null) {
            return false;
        }

        $lock = json_decode($data, true);

        if (($lock['identifier'] ?? null) !== $identifier) {
            return false;
        }

        Redis::del($key);
        Redis::hdel($this->zoneLocksMap($sessionId, $zone->id), "{$row}:{$col}");

        Redis::publish('zone-seat:released', json_encode([
            'session_id' => $sessionId,
            'zone_id' => $zone->id,
            'row' => $row,
            'col' => $col,
            'identifier' => $identifier,
        ]));

        return true;
    }

    public function isSeatLocked(int $sessionId, int $zoneId, int $row, int $col): bool
    {
        return Redis::exists($this->lockKey($sessionId, $zoneId, $row, $col)) === 1;
    }

    public function getReservedCount(int $sessionId, int $zoneId): int
    {
        return (int) Redis::hlen($this->zoneLocksMap($sessionId, $zoneId));
    }

    public function seatExists(Zone $zone, int $row, int $col): bool
    {
        $layout = $zone->seat_layout;

        if (!is_array($layout)) {
            return false;
        }

        if (isset($layout['layout']) && is_array($layout['layout'])) {
            return isset($layout['layout'][$row][$col]) && (int) $layout['layout'][$row][$col] === 1;
        }

        if (isset($layout['seats']) && is_array($layout['seats'])) {
            foreach ($layout['seats'] as $seat) {
                if (($seat['row'] ?? null) === $row && ($seat['col'] ?? null) === $col) {
                    return true;
                }
            }
        }

        return false;
    }

    private function lockKey(int $sessionId, int $zoneId, int $row, int $col): string
    {
        return "zone-seat:lock:{$sessionId}:{$zoneId}:{$row}:{$col}";
    }

    private function zoneLocksMap(int $sessionId, int $zoneId): string
    {
        return "zone-seat:locks:{$sessionId}:{$zoneId}";
    }
}
