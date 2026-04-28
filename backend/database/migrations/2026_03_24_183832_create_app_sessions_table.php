<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->date('date');
            $table->time('time');
            $table->decimal('price', 8, 2)->nullable();
            $table->json('venue_config');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_sessions');
    }
};
