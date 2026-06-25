<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crafting_recipes', function (Blueprint $table) {
            $table->string('category')->default('basic')->after('required_profession');
            $table->foreignId('recipe_scroll_item_id')->nullable()->after('category')->constrained('items')->nullOnDelete();
            $table->string('fixed_result_quality')->nullable()->after('recipe_scroll_item_id');
            $table->boolean('upgradable')->default(true)->after('fixed_result_quality');
        });
    }

    public function down(): void
    {
        Schema::table('crafting_recipes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recipe_scroll_item_id');
            $table->dropColumn(['category', 'fixed_result_quality', 'upgradable']);
        });
    }
};
