<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monster_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->unsignedInteger('character_hp');
            $table->unsignedInteger('monster_hp');
            $table->json('rewards')->nullable();
            $table->timestamps();
        });

        Schema::create('combat_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combat_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('round_number');
            $table->string('actor');
            $table->string('action');
            $table->unsignedInteger('damage')->default(0);
            $table->unsignedInteger('character_hp_after');
            $table->unsignedInteger('monster_hp_after');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combat_rounds');
        Schema::dropIfExists('combats');
    }
};
