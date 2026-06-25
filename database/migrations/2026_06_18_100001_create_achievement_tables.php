<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievement_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->unsignedInteger('reward_points')->default(0);
            $table->string('frequency')->default('none');
            $table->boolean('is_default')->default(false);
            $table->unsignedTinyInteger('difficulty')->nullable();
            $table->unsignedInteger('financial_rate')->nullable();
            $table->boolean('is_primary_income')->default(false);
            $table->timestamps();
        });

        Schema::create('achievement_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_template_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(
                ['character_id', 'achievement_template_id', 'completed_at'],
                'ach_comp_char_tpl_completed_idx'
            );
        });

        Schema::create('energy_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->string('source');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['character_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_transactions');
        Schema::dropIfExists('achievement_completions');
        Schema::dropIfExists('achievement_templates');
    }
};
