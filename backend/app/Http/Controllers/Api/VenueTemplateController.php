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
use Illuminate\Validation\ValidationException;

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
        $this->assertTemplateDataByType($validated);

        $template = DB::transaction(function () use ($validated) {
            $template = VenueTemplate::query()->create([
                'name' => $validated['name'],
                'slug' => $validated['slug'] ?? Str::slug($validated['name'] . '-' . Str::random(5)),
                'description' => $validated['description'] ?? null,
                'template_type' => $validated['template_type'],
                'metadata' => $validated['metadata'] ?? null,
            ]);

            if (($validated['template_type'] ?? 'concert') === 'concert') {
                foreach (($validated['zones'] ?? []) as $index => $zone) {
                    $template->zones()->create([
                        'key' => $zone['key'],
                        'name' => $zone['name'],
                        'zone_type' => $zone['zone_type'] ?? 'general_admission',
                        'capacity' => $this->calculateZoneCapacity($zone),
                        'price' => (float) ($zone['price'] ?? 0),
                        'color' => $zone['color'] ?? '#10B981',
                        'sort_order' => $zone['sort_order'] ?? $index,
                        'seat_layout' => $zone['seat_layout'] ?? null,
                        'shape' => $zone['shape'] ?? null,
                    ]);
                }
            }

            return $template;
        });

        return response()->json(['data' => $template->load('zones')], 201);
    }

    public function update(Request $request, VenueTemplate $venueTemplate): JsonResponse
    {
        $validated = $this->validatePayload($request, true);
        $templateType = $validated['template_type'] ?? $venueTemplate->template_type;
        $normalizedValidated = [
            ...$validated,
            'template_type' => $templateType,
        ];
        $this->assertTemplateDataByType($normalizedValidated, true, $venueTemplate);

        DB::transaction(function () use ($venueTemplate, $validated, $templateType) {
            $venueTemplate->update([
                'name' => $validated['name'] ?? $venueTemplate->name,
                'slug' => $validated['slug'] ?? $venueTemplate->slug,
                'description' => $validated['description'] ?? $venueTemplate->description,
                'template_type' => $templateType,
                'metadata' => $validated['metadata'] ?? $venueTemplate->metadata,
            ]);

            if ($templateType === 'movie') {
                $venueTemplate->zones()->delete();
                return;
            }

            if (isset($validated['zones'])) {
                $venueTemplate->zones()->delete();

                foreach ($validated['zones'] as $index => $zone) {
                    $venueTemplate->zones()->create([
                        'key' => $zone['key'],
                        'name' => $zone['name'],
                        'zone_type' => $zone['zone_type'] ?? 'general_admission',
                        'capacity' => $this->calculateZoneCapacity($zone),
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

            if ($venueTemplate->template_type === 'movie') {
                $layout = $venueTemplate->metadata['layout'] ?? [];

                $session->update([
                    'venue_config' => [
                        'type' => 'grid',
                        'rows' => count($layout),
                        'cols' => count($layout[0] ?? []),
                        'layout' => $layout,
                        'template_id' => $venueTemplate->id,
                        'template_name' => $venueTemplate->name,
                    ],
                ]);
                return;
            }

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

        $session->refresh();

        return response()->json([
            'data' => [
                'session_id' => $session->id,
                'template_id' => $venueTemplate->id,
                'template_type' => $venueTemplate->template_type,
                'zones' => $session->zones,
                'venue_config' => $session->venue_config,
            ],
        ]);
    }

    private function validatePayload(Request $request, bool $partial = false): array
    {
        $rules = [
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'template_type' => [$partial ? 'sometimes' : 'required', 'in:movie,concert'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'metadata.layout' => ['sometimes', 'array', 'min:1'],
            'metadata.layout.*' => ['array', 'min:1'],
            'metadata.layout.*.*' => ['integer', 'in:0,1'],
            'zones' => ['sometimes', 'array', 'min:1'],
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

    private function assertTemplateDataByType(array $validated, bool $partial = false, ?VenueTemplate $existing = null): void
    {
        $type = $validated['template_type'] ?? $existing?->template_type ?? 'concert';

        if ($type === 'movie') {
            $existingMetadata = $existing?->metadata;
            $layout = $validated['metadata']['layout'] ?? (is_array($existingMetadata) ? ($existingMetadata['layout'] ?? null) : null);
            if (!is_array($layout) || count($layout) === 0) {
                throw ValidationException::withMessages([
                    'metadata.layout' => 'Per plantilles de cine has de definir la matriu de seients.',
                ]);
            }
            return;
        }

        $zones = $validated['zones'] ?? ($partial ? $existing?->zones?->toArray() : []);

        if (!is_array($zones) || count($zones) === 0) {
            throw ValidationException::withMessages([
                'zones' => 'Per plantilles de concert has de definir almenys una zona.',
            ]);
        }
    }

    private function calculateZoneCapacity(array $zone): int
    {
        if (($zone['zone_type'] ?? 'general_admission') === 'seated') {
            $layout = $zone['seat_layout']['layout'] ?? [];
            if (is_array($layout)) {
                return (int) collect($layout)->flatten()->filter(fn ($cell) => (int) $cell === 1)->count();
            }
        }

        return (int) ($zone['capacity'] ?? 0);
    }
}
