<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crafting_recipes', function (Blueprint $table) {
            $table->boolean('quality_upgradable')->default(false)->after('upgradable');
        });
    }

    public function down(): void
    {
        Schema::table('crafting_recipes', function (Blueprint $table) {
            $table->dropColumn('quality_upgradable');
        });
    }
};
