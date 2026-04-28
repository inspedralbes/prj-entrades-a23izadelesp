<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AppSession;
use App\Models\OccupiedSeat;
use Illuminate\Support\Facades\Redis;

class SeatLockService
{
    public function lockSeat(int $sessionId, int $row, int $col, string $identifier): bool
    {
        $key = "seat:lock:{$sessionId}:{$row}:{$col}";
        $ttl = (int) config('app.soft_lock_ttl', 300);

        $current = Redis::get($key);

        if ($current === $identifier) {
            Redis::expire($key, $ttl);
            Redis::hset("seat:locks:{$sessionId}", "{$row}:{$col}", $identifier);

            Redis::publish('seat:locked', json_encode([
                'session_id' => $sessionId,
                'row' => $row,
                'col' => $col,
                'identifier' => $identifier,
            ]));

            return true;
        }

        $acquired = Redis::set($key, $identifier, 'EX', $ttl, 'NX');

        if ($acquired === null || $acquired === false) {
            return false;
        }

        Redis::hset("seat:locks:{$sessionId}", "{$row}:{$col}", $identifier);

        Redis::publish('seat:locked', json_encode([
            'session_id' => $sessionId,
            'row' => $row,
            'col' => $col,
            'identifier' => $identifier,
        ]));

        return true;
    }

    public function releaseSeat(int $sessionId, int $row, int $col, string $identifier): bool
    {
        $key = "seat:lock:{$sessionId}:{$row}:{$col}";
        $current = Redis::get($key);

        if ($current === null || $current !== $identifier) {
            return false;
        }

        Redis::del($key);
        Redis::hdel("seat:locks:{$sessionId}", "{$row}:{$col}");

        Redis::publish('seat:released', json_encode([
            'session_id' => $sessionId,
            'row' => $row,
            'col' => $col,
        ]));

        return true;
    }

    public function isSeatLocked(int $sessionId, int $row, int $col): bool
    {
        return Redis::exists("seat:lock:{$sessionId}:{$row}:{$col}") > 0;
    }

    public function getSeatLocks(int $sessionId): array
    {
        return Redis::hgetall("seat:locks:{$sessionId}");
    }

    public function seatExists(int $sessionId, int $row, int $col): bool
    {
        $session = AppSession::find($sessionId);

        if (!$session) {
            return false;
        }

        $config = $session->venue_config;

        if ($config['type'] !== 'grid') {
            return false;
        }

        if ($row < 0 || $row >= $config['rows'] || $col < 0 || $col >= $config['cols']) {
            return false;
        }

        return $config['layout'][$row][$col] === 1;
    }
}
