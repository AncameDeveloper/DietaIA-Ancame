<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_summaries', function (Blueprint $table) {
            $table->unsignedTinyInteger('water_glasses')->default(0)->after('fiber_g');
        });
    }

    public function down(): void
    {
        Schema::table('daily_summaries', function (Blueprint $table) {
            $table->dropColumn('water_glasses');
        });
    }
};
