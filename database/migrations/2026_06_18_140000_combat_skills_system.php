<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->string('catalog_key')->nullable()->unique()->after('id');
            $table->unsignedInteger('min_learn_level')->default(1)->after('description');
            $table->unsignedInteger('teach_price')->default(0)->after('min_learn_level');
        });

        Schema::table('character_skills', function (Blueprint $table) {
            $table->boolean('is_equipped')->default(false)->after('level');
            $table->unsignedTinyInteger('equip_slot')->nullable()->after('is_equipped');
        });

        Schema::table('combats', function (Blueprint $table) {
            $table->json('combat_state')->nullable()->after('monster_hp');
        });

        Schema::table('combat_rounds', function (Blueprint $table) {
            $table->unsignedInteger('heal')->default(0)->after('damage');
            $table->json('meta')->nullable()->after('heal');
        });
    }

    public function down(): void
    {
        Schema::table('combat_rounds', function (Blueprint $table) {
            $table->dropColumn(['heal', 'meta']);
        });

        Schema::table('combats', function (Blueprint $table) {
            $table->dropColumn('combat_state');
        });

        Schema::table('character_skills', function (Blueprint $table) {
            $table->dropColumn(['is_equipped', 'equip_slot']);
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->dropColumn(['catalog_key', 'min_learn_level', 'teach_price']);
        });
    }
};
