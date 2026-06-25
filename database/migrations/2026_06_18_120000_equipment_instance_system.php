<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('catalog_key')->nullable()->after('name');
            $table->string('equipment_source')->nullable()->after('type');
            $table->string('set_key')->nullable()->after('equipment_source');
            $table->unsignedTinyInteger('item_level')->default(1)->after('set_key');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            // MySQL привязывает FK character_id/item_id к unique-индексу;
            // сначала создаём отдельные индексы, иначе dropUnique падает с error 1553.
            $table->index('character_id', 'inv_char_id_idx');
            $table->index('item_id', 'inv_item_id_idx');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropUnique(['character_id', 'item_id']);
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('quality')->nullable()->after('quantity');
            $table->unsignedTinyInteger('equipment_level')->nullable()->after('quality');
            $table->string('equipment_source')->nullable()->after('equipment_level');
            $table->unsignedSmallInteger('upgrade_count')->default(0)->after('equipment_source');
            $table->index(['character_id', 'item_id'], 'inv_char_item_idx');
        });

        Schema::table('merchant_offers', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->dropUnique(['poi_type', 'item_id']);
            $table->unique(['location_id', 'poi_type', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('merchant_offers', function (Blueprint $table) {
            $table->dropUnique(['location_id', 'poi_type', 'item_id']);
            $table->unique(['poi_type', 'item_id']);
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex('inv_char_item_idx');
            $table->dropColumn(['quality', 'equipment_level', 'equipment_source', 'upgrade_count']);
            $table->dropIndex('inv_char_id_idx');
            $table->dropIndex('inv_item_id_idx');
            $table->unique(['character_id', 'item_id']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['catalog_key', 'equipment_source', 'set_key', 'item_level']);
        });
    }
};
