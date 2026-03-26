<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\AppSession;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController
{
    public function __construct(
        private QueueService $queueService,
    ) {}

    public function join(Request $request, AppSession $session): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $position = $this->queueService->join(
            $session->id,
            $request->input('identifier'),
        );

        return response()->json([
            'position' => $position,
            'active' => $position === 0,
        ]);
    }

    public function position(Request $request, AppSession $session): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $position = $this->queueService->getPosition(
            $session->id,
            $request->input('identifier'),
        );

        return response()->json([
            'position' => $position,
            'waiting' => $position !== null,
            'active' => $this->queueService->isActive(
                $session->id,
                $request->input('identifier'),
            ),
        ]);
    }

    public function admit(Request $request, AppSession $session): JsonResponse
    {
        $request->validate([
            'batch_size' => 'required|integer|min:1',
        ]);

        $released = $this->queueService->releaseBatch(
            $session->id,
            $request->input('batch_size'),
        );

        return response()->json([
            'released' => $released,
        ]);
    }
}
