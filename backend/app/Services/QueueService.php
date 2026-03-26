<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class QueueService
{
    public function join(int $sessionId, string $identifier): int
    {
        if ($this->isActive($sessionId, $identifier)) {
            return 0;
        }

        $existingPosition = $this->getPosition($sessionId, $identifier);

        if ($existingPosition !== null) {
            return $existingPosition;
        }

        Redis::rpush("queue:{$sessionId}", $identifier);

        $position = Redis::llen("queue:{$sessionId}");

        Redis::set("queue:position:{$sessionId}:{$identifier}", $position);

        Redis::publish('queue:updated', json_encode([
            'session_id' => $sessionId,
            'identifier' => $identifier,
            'position' => $position,
        ]));

        return $position;
    }

    public function getPosition(int $sessionId, string $identifier): ?int
    {
        if ($this->isActive($sessionId, $identifier)) {
            return null;
        }

        $queue = Redis::lrange("queue:{$sessionId}", 0, -1);

        $index = array_search($identifier, $queue);

        if ($index === false) {
            return null;
        }

        return $index + 1;
    }

    public function releaseBatch(int $sessionId, int $batchSize): array
    {
        $released = [];

        for ($i = 0; $i < $batchSize; $i++) {
            $identifier = Redis::lpop("queue:{$sessionId}");

            if ($identifier === null) {
                break;
            }

            Redis::sadd("queue:{$sessionId}:active", $identifier);
            Redis::del("queue:position:{$sessionId}:{$identifier}");

            $released[] = $identifier;

            Redis::publish('queue:updated', json_encode([
                'session_id' => $sessionId,
                'identifier' => $identifier,
                'status' => 'admitted',
            ]));
        }

        // Update positions for remaining users in queue
        $remaining = Redis::lrange("queue:{$sessionId}", 0, -1);
        foreach ($remaining as $index => $id) {
            $pos = $index + 1;
            Redis::set("queue:position:{$sessionId}:{$id}", $pos);

            Redis::publish('queue:updated', json_encode([
                'session_id' => $sessionId,
                'identifier' => $id,
                'position' => $pos,
            ]));
        }

        return $released;
    }

    public function isActive(int $sessionId, string $identifier): bool
    {
        return (bool) Redis::sismember("queue:{$sessionId}:active", $identifier);
    }
}
