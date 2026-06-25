<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->unsignedInteger('min_power')->default(0);
            $table->boolean('is_safe')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('location_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('to_location_id')->constrained('locations')->cascadeOnDelete();
            $table->unsignedInteger('energy_cost')->default(1);
            $table->timestamps();

            $table->unique(['from_location_id', 'to_location_id']);
        });

        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('current_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->unsignedInteger('hp');
            $table->unsignedInteger('max_hp');
            $table->unsignedInteger('energy')->default(0);
            $table->unsignedInteger('money')->default(0);
            $table->unsignedInteger('xp')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('strength')->default(5);
            $table->unsignedInteger('defense')->default(5);
            $table->unsignedInteger('power')->default(0);
            $table->string('profession')->default('none');
            $table->timestamp('last_hp_reset_at')->nullable();
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('slot')->nullable();
            $table->json('stats')->nullable();
            $table->unsignedInteger('buy_price')->default(0);
            $table->unsignedInteger('sell_price')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['character_id', 'item_id']);
        });

        Schema::create('equipped_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('slot');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['character_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipped_items');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('items');
        Schema::dropIfExists('characters');
        Schema::dropIfExists('location_connections');
        Schema::dropIfExists('locations');
    }
};
