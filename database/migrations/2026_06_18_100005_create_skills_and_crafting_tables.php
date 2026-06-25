<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->unsignedInteger('power');
            $table->unsignedInteger('priority')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('character_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('level')->default(1);
            $table->timestamps();

            $table->unique(['character_id', 'skill_id']);
        });

        Schema::create('crafting_recipes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('result_item_id')->constrained('items')->cascadeOnDelete();
            $table->unsignedInteger('result_quantity')->default(1);
            $table->unsignedInteger('energy_cost')->default(1);
            $table->string('required_profession')->default('none');
            $table->json('ingredients');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crafting_recipes');
        Schema::dropIfExists('character_skills');
        Schema::dropIfExists('skills');
    }
};
