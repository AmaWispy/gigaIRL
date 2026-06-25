<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->unsignedSmallInteger('entry_energy')->default(10)->after('description');
            $table->unsignedSmallInteger('floor_energy')->default(10)->after('entry_energy');
            $table->unsignedSmallInteger('combat_energy')->default(1)->after('floor_energy');
            $table->unsignedSmallInteger('resource_energy')->default(1)->after('combat_energy');
            $table->unsignedSmallInteger('treasure_energy')->default(1)->after('resource_energy');
            $table->unsignedTinyInteger('floors_total')->default(10)->after('treasure_energy');
            $table->unsignedTinyInteger('rare_floor')->default(5)->after('floors_total');
            $table->unsignedTinyInteger('boss_floor')->default(10)->after('rare_floor');
            $table->unsignedInteger('pass_price')->default(500)->after('boss_floor');
            $table->unsignedTinyInteger('resource_pile_chance')->default(35)->after('pass_price');
            $table->unsignedTinyInteger('treasure_chance')->default(20)->after('resource_pile_chance');
            $table->unsignedTinyInteger('resource_quantity_min')->default(2)->after('treasure_chance');
            $table->unsignedTinyInteger('resource_quantity_max')->default(4)->after('resource_quantity_min');
            $table->unsignedTinyInteger('rare_resource_from_pile_chance')->default(15)->after('resource_quantity_max');
            $table->unsignedTinyInteger('rare_resource_from_mob_chance')->default(8)->after('rare_resource_from_pile_chance');
            $table->unsignedInteger('treasure_money_min')->default(15)->after('rare_resource_from_mob_chance');
            $table->unsignedInteger('treasure_money_max')->default(75)->after('treasure_money_min');
            $table->decimal('mob_money_multiplier', 4, 2)->default(1.5)->after('treasure_money_max');
            $table->unsignedTinyInteger('set_equipment_non_weapon_chance')->default(70)->after('mob_money_multiplier');
            $table->decimal('xp_multiplier_normal', 4, 2)->default(2.0)->after('set_equipment_non_weapon_chance');
            $table->decimal('xp_multiplier_rare', 4, 2)->default(3.5)->after('xp_multiplier_normal');
            $table->decimal('xp_multiplier_boss', 4, 2)->default(6.0)->after('xp_multiplier_rare');
            $table->unsignedTinyInteger('clear_bonus_xp_percent')->default(50)->after('xp_multiplier_boss');
            $table->unsignedInteger('clear_bonus_money')->default(100)->after('clear_bonus_xp_percent');
            $table->unsignedTinyInteger('explorer_seal_on_clear')->default(1)->after('clear_bonus_money');
            $table->unsignedSmallInteger('exploration_entrance_energy')->default(2)->after('explorer_seal_on_clear');
            $table->unsignedTinyInteger('exploration_entrance_chance')->default(8)->after('exploration_entrance_energy');
        });

        Schema::create('dungeon_loot_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dungeon_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->string('reward_type');
            $table->unsignedTinyInteger('chance_percent')->default(0);
            $table->timestamps();

            $table->unique(['dungeon_id', 'source', 'reward_type']);
        });

        Schema::create('dungeon_resource_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dungeon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('pool');
            $table->timestamps();

            $table->unique(['dungeon_id', 'item_id', 'pool']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dungeon_resource_pools');
        Schema::dropIfExists('dungeon_loot_rules');

        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropColumn([
                'entry_energy',
                'floor_energy',
                'combat_energy',
                'resource_energy',
                'treasure_energy',
                'floors_total',
                'rare_floor',
                'boss_floor',
                'pass_price',
                'resource_pile_chance',
                'treasure_chance',
                'resource_quantity_min',
                'resource_quantity_max',
                'rare_resource_from_pile_chance',
                'rare_resource_from_mob_chance',
                'treasure_money_min',
                'treasure_money_max',
                'mob_money_multiplier',
                'set_equipment_non_weapon_chance',
                'xp_multiplier_normal',
                'xp_multiplier_rare',
                'xp_multiplier_boss',
                'clear_bonus_xp_percent',
                'clear_bonus_money',
                'explorer_seal_on_clear',
                'exploration_entrance_energy',
                'exploration_entrance_chance',
            ]);
        });
    }
};
