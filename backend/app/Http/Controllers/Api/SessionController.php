<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\SessionResource;
use App\Models\AppSession;
use App\Models\OccupiedSeat;
use App\Models\OccupiedZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController
{
    public function show(AppSession $session): JsonResponse
    {
        $session->load('event');

        return (new SessionResource($session))->response();
    }

    public function seats(Request $request, AppSession $session): JsonResponse
    {
        $config = $session->venue_config;

        if ($config['type'] === 'grid') {
            return $this->gridSeats($session, $config);
        }

        return $this->zoneSeats($session, $config);
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

    private function zoneSeats(AppSession $session, array $config): JsonResponse
    {
        $occupiedCounts = OccupiedZone::where('session_id', $session->id)
            ->selectRaw('zone_id, SUM(quantity) as total')
            ->groupBy('zone_id')
            ->pluck('total', 'zone_id');

        $zones = collect($config['zones'])->map(function ($zone) use ($occupiedCounts) {
            $occupied = (int) ($occupiedCounts[$zone['id']] ?? 0);

            return [
                'id' => $zone['id'],
                'name' => $zone['name'],
                'capacity' => $zone['capacity'],
                'available' => $zone['capacity'] - $occupied,
                'price' => $zone['price'],
                'color' => $zone['color'],
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
