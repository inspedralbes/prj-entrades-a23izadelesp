<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occupied_zone_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->constrained('app_sessions')->onDelete('cascade');
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->integer('row');
            $table->integer('col');
            $table->timestamps();

            $table->unique(['session_id', 'zone_id', 'row', 'col'], 'occupied_zone_seat_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupied_zone_seats');
    }
};
