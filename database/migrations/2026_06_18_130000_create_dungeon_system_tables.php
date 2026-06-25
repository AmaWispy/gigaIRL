<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->unsignedTinyInteger('world_tier')->nullable()->after('min_power');
        });

        Schema::create('dungeons', function (Blueprint $table) {
            $table->id();
            $table->string('catalog_key')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('tier');
            $table->string('set_key');
            $table->unsignedTinyInteger('item_level');
            $table->unsignedInteger('min_power')->default(0);
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('dungeon_monsters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dungeon_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->foreignId('monster_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['dungeon_id', 'monster_id']);
        });

        Schema::create('character_dungeon_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dungeon_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['character_id', 'dungeon_id']);
        });

        Schema::create('dungeon_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dungeon_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('current_floor')->default(1);
            $table->unsignedInteger('character_hp');
            $table->unsignedInteger('run_xp_earned')->default(0);
            $table->unsignedInteger('run_money_earned')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('completed')->default(false);
            $table->boolean('failed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('dungeon_floor_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dungeon_run_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('floor');
            $table->foreignId('monster_id')->constrained()->cascadeOnDelete();
            $table->string('mob_role');
            $table->boolean('mob_defeated')->default(false);
            $table->boolean('has_resource_pile')->default(false);
            $table->boolean('resource_claimed')->default(false);
            $table->boolean('has_treasure')->default(false);
            $table->boolean('treasure_claimed')->default(false);
            $table->timestamps();

            $table->unique(['dungeon_run_id', 'floor']);
        });

        Schema::table('combats', function (Blueprint $table) {
            $table->foreignId('dungeon_run_id')->nullable()->after('monster_id')->constrained()->nullOnDelete();
            $table->foreignId('dungeon_floor_state_id')->nullable()->after('dungeon_run_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('combats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dungeon_floor_state_id');
            $table->dropConstrainedForeignId('dungeon_run_id');
        });

        Schema::dropIfExists('dungeon_floor_states');
        Schema::dropIfExists('dungeon_runs');
        Schema::dropIfExists('character_dungeon_unlocks');
        Schema::dropIfExists('dungeon_monsters');
        Schema::dropIfExists('dungeons');

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('world_tier');
        });
    }
};
