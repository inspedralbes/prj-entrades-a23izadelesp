<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\SessionResource;
use App\Models\AppSession;
use App\Models\OccupiedZoneSeat;
use App\Models\OccupiedSeat;
use App\Models\OccupiedZone;
use App\Models\Zone;
use App\Services\ZoneLockService;
use App\Services\ZoneSeatLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController
{
    public function __construct(
        private ZoneLockService $zoneLockService,
        private ZoneSeatLockService $zoneSeatLockService,
    ) {}

    public function show(AppSession $session): JsonResponse
    {
        $session->load(['event', 'zones']);

        return (new SessionResource($session))->response();
    }

    public function seats(Request $request, AppSession $session): JsonResponse
    {
        $config = $session->venue_config;

        if (($config['type'] ?? null) === 'grid') {
            return $this->gridSeats($session, $config);
        }

        return $this->zoneSeats($session);
    }

    public function zoneSeatsDetail(AppSession $session, Zone $zone): JsonResponse
    {
        if ((int) $zone->session_id !== (int) $session->id || $zone->zone_type !== 'seated') {
            return response()->json(['error' => 'Zona no vàlida'], 422);
        }

        $layout = $zone->seat_layout;
        if (!is_array($layout) || !isset($layout['layout']) || !is_array($layout['layout'])) {
            return response()->json(['error' => 'Aquesta zona no té distribució de seients'], 422);
        }

        $occupied = OccupiedZoneSeat::query()
            ->where('session_id', $session->id)
            ->where('zone_id', $zone->id)
            ->get()
            ->keyBy(fn ($seat) => "{$seat->row}:{$seat->col}");

        $grid = [];
        foreach ($layout['layout'] as $rowIndex => $rowData) {
            $cells = [];
            foreach ($rowData as $colIndex => $cell) {
                if ((int) $cell !== 1) {
                    $cells[] = null;
                    continue;
                }

                $key = "{$rowIndex}:{$colIndex}";
                $locked = $this->zoneSeatLockService->isSeatLocked($session->id, $zone->id, $rowIndex, $colIndex);
                $status = $occupied->has($key) ? 'occupied' : ($locked ? 'blocked' : 'free');

                $cells[] = [
                    'row' => $rowIndex,
                    'col' => $colIndex,
                    'status' => $status,
                    'label' => sprintf('%s%d', chr(65 + $rowIndex), $colIndex + 1),
                    'price' => (float) $zone->price,
                ];
            }
            $grid[] = $cells;
        }

        return response()->json([
            'data' => [
                'zone' => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'price' => (float) $zone->price,
                    'color' => $zone->color,
                ],
                'grid' => $grid,
            ],
        ]);
    }

    private function gridSeats(AppSession $session, array $config): JsonResponse
    {
        $occupied = OccupiedSeat::where('session_id', $session->id)
            ->get()
            ->keyBy(fn ($seat) => "{$seat->row}:{$seat->col}");

        $grid = [];
        for ($row = 0; $row < $config['rows']; $row++) {
            $rowData = [];
            for ($col = 0; $col < $config['cols']; $col++) {
                if ($config['layout'][$row][$col] === 0) {
                    $rowData[] = null;
                } else {
                    $key = "{$row}:{$col}";
                    $rowData[] = [
                        'row' => $row,
                        'col' => $col,
                        'status' => $occupied->has($key) ? 'occupied' : 'free',
                    ];
                }
            }
            $grid[] = $rowData;
        }

        return response()->json([
            'data' => [
                'type' => 'grid',
                'grid' => $grid,
            ],
        ]);
    }

    private function zoneSeats(AppSession $session): JsonResponse
    {
        if ($session->zones()->count() === 0) {
            return $this->legacyZoneSeats($session);
        }

        $occupiedCounts = OccupiedZone::query()
            ->where('session_id', $session->id)
            ->selectRaw('zone_id, SUM(quantity) as total')
            ->groupBy('zone_id')
            ->pluck('total', 'zone_id');

        $occupiedSeatCounts = OccupiedZoneSeat::query()
            ->where('session_id', $session->id)
            ->selectRaw('zone_id, COUNT(*) as total')
            ->groupBy('zone_id')
            ->pluck('total', 'zone_id');

        $zones = $session->zones()->get()->map(function (Zone $zone) use ($session, $occupiedCounts, $occupiedSeatCounts) {
            $zoneId = (int) $zone->id;

            if ($zone->zone_type === 'seated') {
                $layout = is_array($zone->seat_layout) ? $zone->seat_layout : [];
                $totalSeats = collect($layout['layout'] ?? [])->flatten()->filter(fn ($cell) => (int) $cell === 1)->count();
                $occupied = (int) ($occupiedSeatCounts[$zoneId] ?? 0);
                $reserved = $this->zoneSeatLockService->getReservedCount($session->id, $zoneId);
                $available = max(0, $totalSeats - $occupied - $reserved);
                $capacity = $totalSeats;
            } else {
                $occupied = (int) ($occupiedCounts[(string) $zoneId] ?? 0);
                $available = $this->zoneLockService->getAvailabilityByZoneId($session->id, $zoneId);
                $capacity = (int) $zone->capacity;
            }

            return [
                'id' => $zoneId,
                'key' => $zone->key,
                'name' => $zone->name,
                'zone_type' => $zone->zone_type,
                'capacity' => $capacity,
                'available' => $available,
                'price' => (float) $zone->price,
                'color' => $zone->color,
                'occupied' => $occupied,
            ];
        });

        return response()->json([
            'data' => [
                'type' => 'zones',
                'zones' => $zones,
            ],
        ]);
    }

    private function legacyZoneSeats(AppSession $session): JsonResponse
    {
        $configZones = $session->venue_config['zones'] ?? [];

        $zones = collect($configZones)->map(function (array $zone) use ($session): array {
            $zoneId = (string) ($zone['id'] ?? '');
            $capacity = (int) ($zone['capacity'] ?? 0);

            return [
                'id' => $zoneId,
                'key' => $zoneId,
                'name' => (string) ($zone['name'] ?? 'Zona'),
                'zone_type' => 'general_admission',
                'capacity' => $capacity,
                'available' => $this->zoneLockService->getAvailabilityByZoneId($session->id, $zoneId),
                'price' => (float) ($zone['price'] ?? 0),
                'color' => (string) ($zone['color'] ?? '#10B981'),
                'occupied' => max(0, $capacity - $this->zoneLockService->getAvailabilityByZoneId($session->id, $zoneId)),
            ];
        });

        return response()->json([
            'data' => [
                'type' => 'zones',
                'zones' => $zones,
            ],
        ]);
    }
}
