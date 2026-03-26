<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\AppSession;
use App\Services\SeatLockService;
use App\Services\ZoneLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeatController
{
    public function __construct(
        private SeatLockService $seatLockService,
        private ZoneLockService $zoneLockService,
    ) {}

    public function lockSeat(Request $request, AppSession $session): JsonResponse
    {
        $request->validate([
            'row' => 'required|integer',
            'col' => 'required|integer',
            'identifier' => 'required|string',
        ]);

        $row = $request->input('row');
        $col = $request->input('col');
        $identifier = $request->input('identifier');

        if (!$this->seatLockService->seatExists($session->id, $row, $col)) {
            return response()->json(['error' => 'Seat does not exist in layout'], 422);
        }

        if ($this->seatLockService->isSeatLocked($session->id, $row, $col)) {
            $current = \Illuminate\Support\Facades\Redis::get("seat:lock:{$session->id}:{$row}:{$col}");
            if ($current !== $identifier) {
                return response()->json(['error' => 'Seat is already locked'], 422);
            }
        }

        $locked = $this->seatLockService->lockSeat($session->id, $row, $col, $identifier);

        return response()->json(['locked' => $locked]);
    }

    public function unlockSeat(Request $request, AppSession $session): JsonResponse
    {
        $request->validate([
            'row' => 'required|integer',
            'col' => 'required|integer',
            'identifier' => 'required|string',
        ]);

        $row = $request->input('row');
        $col = $request->input('col');
        $identifier = $request->input('identifier');

        $released = $this->seatLockService->releaseSeat($session->id, $row, $col, $identifier);

        if (!$released) {
            return response()->json(['error' => 'Seat is not locked by this user'], 422);
        }

        return response()->json(['released' => true]);
    }

    public function lockZone(Request $request, AppSession $session): JsonResponse
    {
        $request->validate([
            'zone_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'identifier' => 'required|string',
        ]);

        $lockId = $this->zoneLockService->lockZone(
            $session->id,
            $request->input('zone_id'),
            $request->input('quantity'),
            $request->input('identifier'),
        );

        if ($lockId === false) {
            return response()->json(['error' => 'Zone capacity exceeded or zone not found'], 422);
        }

        return response()->json([
            'locked' => true,
            'lock_id' => $lockId,
        ]);
    }

    public function unlockZone(Request $request, AppSession $session): JsonResponse
    {
        $request->validate([
            'zone_id' => 'required|string',
            'lock_id' => 'required|string',
            'identifier' => 'required|string',
        ]);

        $released = $this->zoneLockService->releaseZone(
            $session->id,
            $request->input('zone_id'),
            $request->input('lock_id'),
            $request->input('identifier'),
        );

        if (!$released) {
            return response()->json(['error' => 'Zone lock not found or not owned by user'], 422);
        }

        return response()->json(['released' => true]);
    }
}
