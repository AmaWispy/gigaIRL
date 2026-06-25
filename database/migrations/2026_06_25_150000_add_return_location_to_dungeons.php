<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->foreignId('return_location_id')
                ->nullable()
                ->after('location_id')
                ->constrained('locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('return_location_id');
        });
    }
};
