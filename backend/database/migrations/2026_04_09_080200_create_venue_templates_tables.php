<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('template_type')->default('concert');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('venue_template_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_template_id')->constrained('venue_templates')->onDelete('cascade');
            $table->string('key');
            $table->string('name');
            $table->string('zone_type')->default('general_admission');
            $table->integer('capacity')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->string('color')->default('#10B981');
            $table->integer('sort_order')->default(0);
            $table->json('seat_layout')->nullable();
            $table->json('shape')->nullable();
            $table->timestamps();

            $table->unique(['venue_template_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_template_zones');
        Schema::dropIfExists('venue_templates');
    }
};
