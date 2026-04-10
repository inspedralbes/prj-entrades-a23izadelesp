<?php

use App\Models\AppSession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->string('key')->nullable()->after('session_id');
            $table->string('zone_type')->default('general_admission')->after('name');
            $table->integer('sort_order')->default(0)->after('color');
            $table->json('seat_layout')->nullable()->after('sort_order');

            $table->index(['session_id', 'zone_type']);
            $table->unique(['session_id', 'key']);
        });

        AppSession::query()
            ->whereNotNull('venue_config')
            ->chunkById(100, function ($sessions): void {
                foreach ($sessions as $session) {
                    $config = $session->venue_config;

                    if (!is_array($config) || ($config['type'] ?? null) !== 'zones') {
                        continue;
                    }

                    $zones = $config['zones'] ?? [];
                    if (!is_array($zones)) {
                        continue;
                    }

                    foreach ($zones as $index => $zone) {
                        if (!is_array($zone)) {
                            continue;
                        }

                        $key = (string) ($zone['id'] ?? ('zone_' . $index));
                        $name = (string) ($zone['name'] ?? ('Zona ' . ($index + 1)));
                        $capacity = (int) ($zone['capacity'] ?? 0);
                        $price = (float) ($zone['price'] ?? 0);
                        $color = (string) ($zone['color'] ?? '#10B981');
                        $zoneType = (string) ($zone['zone_type'] ?? 'general_admission');

                        $exists = \App\Models\Zone::query()
                            ->where('session_id', $session->id)
                            ->where('key', $key)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        \App\Models\Zone::query()->create([
                            'session_id' => $session->id,
                            'key' => $key,
                            'name' => $name,
                            'zone_type' => $zoneType,
                            'price' => $price,
                            'capacity' => $capacity,
                            'available' => $capacity,
                            'color' => $color,
                            'sort_order' => $index,
                            'seat_layout' => is_array($zone['seat_layout'] ?? null) ? $zone['seat_layout'] : null,
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropUnique(['session_id', 'key']);
            $table->dropIndex(['session_id', 'zone_type']);

            $table->dropColumn(['key', 'zone_type', 'sort_order', 'seat_layout']);
        });
    }
};
