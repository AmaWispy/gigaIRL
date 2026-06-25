<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_offers', function (Blueprint $table) {
            $table->foreignId('cost_item_id')->nullable()->after('buy_price')->constrained('items')->nullOnDelete();
            $table->unsignedInteger('cost_quantity')->default(1)->after('cost_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_offers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cost_item_id');
            $table->dropColumn('cost_quantity');
        });
    }
};
