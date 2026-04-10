<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSession;
use App\Models\VenueTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VenueTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $templates = VenueTemplate::query()->with('zones')->latest()->get();

        return response()->json(['data' => $templates]);
    }

    public function show(VenueTemplate $venueTemplate): JsonResponse
    {
        return response()->json(['data' => $venueTemplate->load('zones')]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $template = DB::transaction(function () use ($validated) {
            $template = VenueTemplate::query()->create([
                'name' => $validated['name'],
                'slug' => $validated['slug'] ?? Str::slug($validated['name'] . '-' . Str::random(5)),
                'description' => $validated['description'] ?? null,
                'template_type' => $validated['template_type'] ?? 'concert',
                'metadata' => $validated['metadata'] ?? null,
            ]);

            foreach ($validated['zones'] as $index => $zone) {
                $template->zones()->create([
                    'key' => $zone['key'],
                    'name' => $zone['name'],
                    'zone_type' => $zone['zone_type'] ?? 'general_admission',
                    'capacity' => (int) ($zone['capacity'] ?? 0),
                    'price' => (float) ($zone['price'] ?? 0),
                    'color' => $zone['color'] ?? '#10B981',
                    'sort_order' => $zone['sort_order'] ?? $index,
                    'seat_layout' => $zone['seat_layout'] ?? null,
                    'shape' => $zone['shape'] ?? null,
                ]);
            }

            return $template;
        });

        return response()->json(['data' => $template->load('zones')], 201);
    }

    public function update(Request $request, VenueTemplate $venueTemplate): JsonResponse
    {
        $validated = $this->validatePayload($request, true);

        DB::transaction(function () use ($venueTemplate, $validated) {
            $venueTemplate->update([
                'name' => $validated['name'] ?? $venueTemplate->name,
                'slug' => $validated['slug'] ?? $venueTemplate->slug,
                'description' => $validated['description'] ?? $venueTemplate->description,
                'template_type' => $validated['template_type'] ?? $venueTemplate->template_type,
                'metadata' => $validated['metadata'] ?? $venueTemplate->metadata,
            ]);

            if (isset($validated['zones'])) {
                $venueTemplate->zones()->delete();

                foreach ($validated['zones'] as $index => $zone) {
                    $venueTemplate->zones()->create([
                        'key' => $zone['key'],
                        'name' => $zone['name'],
                        'zone_type' => $zone['zone_type'] ?? 'general_admission',
                        'capacity' => (int) ($zone['capacity'] ?? 0),
                        'price' => (float) ($zone['price'] ?? 0),
                        'color' => $zone['color'] ?? '#10B981',
                        'sort_order' => $zone['sort_order'] ?? $index,
                        'seat_layout' => $zone['seat_layout'] ?? null,
                        'shape' => $zone['shape'] ?? null,
                    ]);
                }
            }
        });

        return response()->json(['data' => $venueTemplate->fresh()->load('zones')]);
    }

    public function destroy(VenueTemplate $venueTemplate): JsonResponse
    {
        $venueTemplate->delete();

        return response()->json(['deleted' => true]);
    }

    public function applyToSession(Request $request, VenueTemplate $venueTemplate, AppSession $session): JsonResponse
    {
        DB::transaction(function () use ($venueTemplate, $session) {
            $session->zones()->delete();

            foreach ($venueTemplate->zones as $index => $zone) {
                $session->zones()->create([
                    'key' => $zone->key,
                    'name' => $zone->name,
                    'zone_type' => $zone->zone_type,
                    'capacity' => $zone->capacity,
                    'available' => $zone->capacity,
                    'price' => $zone->price,
                    'color' => $zone->color,
                    'sort_order' => $zone->sort_order ?? $index,
                    'seat_layout' => $zone->seat_layout,
                ]);
            }

            $session->update([
                'venue_config' => [
                    'type' => 'zones',
                    'template_id' => $venueTemplate->id,
                    'template_name' => $venueTemplate->name,
                ],
            ]);
        });

        return response()->json([
            'data' => [
                'session_id' => $session->id,
                'template_id' => $venueTemplate->id,
                'zones' => $session->fresh()->zones,
            ],
        ]);
    }

    private function validatePayload(Request $request, bool $partial = false): array
    {
        $rules = [
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'template_type' => ['sometimes', 'string', 'max:50'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'zones' => [$partial ? 'sometimes' : 'required', 'array', 'min:1'],
            'zones.*.key' => ['required_with:zones', 'string', 'max:100'],
            'zones.*.name' => ['required_with:zones', 'string', 'max:255'],
            'zones.*.zone_type' => ['required_with:zones', 'in:general_admission,seated'],
            'zones.*.capacity' => ['required_with:zones', 'integer', 'min:0'],
            'zones.*.price' => ['required_with:zones', 'numeric', 'min:0'],
            'zones.*.color' => ['sometimes', 'string', 'max:20'],
            'zones.*.sort_order' => ['sometimes', 'integer', 'min:0'],
            'zones.*.seat_layout' => ['nullable', 'array'],
            'zones.*.shape' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }
}
