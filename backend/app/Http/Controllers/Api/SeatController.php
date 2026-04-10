<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\AppSession;
use App\Models\Zone;
use App\Services\SeatLockService;
use App\Services\ZoneLockService;
use App\Services\ZoneSeatLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeatController
{
    public function __construct(
        private SeatLockService $seatLockService,
        private ZoneLockService $zoneLockService,
        private ZoneSeatLockService $zoneSeatLockService,
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
            'zone_id' => 'required',
            'quantity' => 'required|integer|min:1',
            'identifier' => 'required|string',
        ]);

        $zoneIdInput = $request->input('zone_id');
        $zoneId = is_numeric((string) $zoneIdInput) ? (int) $zoneIdInput : (string) $zoneIdInput;

        $lockId = $this->zoneLockService->lockZone(
            $session->id,
            $zoneId,
            $request->input('quantity'),
            $request->input('identifier'),
        );

        if ($lockId === false) {
            return response()->json(['error' => 'Zone capacity exceeded or zone not found'], 422);
        }

        return response()->json([
            'locked' => true,
            'lock_id' => $lockId,
            'available' => $this->zoneLockService->getAvailabilityByZoneId($session->id, $zoneIdInput),
        ]);
    }

    public function unlockZone(Request $request, AppSession $session): JsonResponse
    {
        $request->validate([
            'zone_id' => 'required',
            'lock_id' => 'required|string',
            'identifier' => 'required|string',
        ]);

        $zoneIdInput = $request->input('zone_id');
        $zoneId = is_numeric((string) $zoneIdInput) ? (int) $zoneIdInput : (string) $zoneIdInput;

        $released = $this->zoneLockService->releaseZone(
            $session->id,
            $zoneId,
            $request->input('lock_id'),
            $request->input('identifier'),
        );

        if (!$released) {
            return response()->json(['error' => 'Zone lock not found or not owned by user'], 422);
        }

        return response()->json([
            'released' => true,
            'available' => $this->zoneLockService->getAvailabilityByZoneId($session->id, $zoneIdInput),
        ]);
    }

    public function lockZoneSeat(Request $request, AppSession $session, Zone $zone): JsonResponse
    {
        if ((int) $zone->session_id !== (int) $session->id) {
            return response()->json(['error' => 'Zona no vàlida per aquesta sessió'], 422);
        }

        $request->validate([
            'row' => 'required|integer|min:0',
            'col' => 'required|integer|min:0',
            'identifier' => 'required|string',
        ]);

        $lockId = $this->zoneSeatLockService->lockSeat(
            $session->id,
            $zone,
            (int) $request->input('row'),
            (int) $request->input('col'),
            $request->input('identifier'),
        );

        if ($lockId === false) {
            return response()->json(['error' => 'Seient no disponible'], 422);
        }

        return response()->json([
            'locked' => true,
            'lock_id' => $lockId,
        ]);
    }

    public function unlockZoneSeat(Request $request, AppSession $session, Zone $zone): JsonResponse
    {
        if ((int) $zone->session_id !== (int) $session->id) {
            return response()->json(['error' => 'Zona no vàlida per aquesta sessió'], 422);
        }

        $request->validate([
            'row' => 'required|integer|min:0',
            'col' => 'required|integer|min:0',
            'identifier' => 'required|string',
        ]);

        $released = $this->zoneSeatLockService->releaseSeat(
            $session->id,
            $zone,
            (int) $request->input('row'),
            (int) $request->input('col'),
            $request->input('identifier'),
        );

        if (!$released) {
            return response()->json(['error' => 'Seient no bloquejat per aquest usuari'], 422);
        }

        return response()->json(['released' => true]);
    }
}
