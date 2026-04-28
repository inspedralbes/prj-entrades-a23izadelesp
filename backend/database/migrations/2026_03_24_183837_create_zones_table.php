<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('app_sessions')->onDelete('cascade');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('capacity');
            $table->integer('available');
            $table->string('color')->default('#10B981');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};