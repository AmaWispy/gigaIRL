<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('tier')->nullable()->after('type');
            $table->string('tier_emoji')->nullable()->after('tier');
        });

        Schema::table('monsters', function (Blueprint $table) {
            $table->text('flavor_text')->nullable()->after('name');
            $table->unsignedInteger('energy_cost')->default(1)->after('tier');
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->string('blacksmith_rank')->default('apprentice')->after('profession');
        });

        Schema::create('merchant_offers', function (Blueprint $table) {
            $table->id();
            $table->string('poi_type');
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('buy_price');
            $table->unsignedInteger('stock')->nullable();
            $table->timestamps();

            $table->unique(['poi_type', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_offers');

        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('blacksmith_rank');
        });

        Schema::table('monsters', function (Blueprint $table) {
            $table->dropColumn(['flavor_text', 'energy_cost']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['tier', 'tier_emoji']);
        });
    }
};
