<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exploration_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('exploration_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploration_session_id')->constrained()->cascadeOnDelete();
            $table->string('action_type');
            $table->unsignedInteger('energy_cost');
            $table->json('payload')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exploration_actions');
        Schema::dropIfExists('exploration_sessions');
    }
};
