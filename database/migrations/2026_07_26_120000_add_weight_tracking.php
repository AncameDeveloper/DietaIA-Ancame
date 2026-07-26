<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->decimal('start_weight_kg', 5, 2)->nullable()->after('weight_kg');
            $table->decimal('target_weight_kg', 5, 2)->nullable()->after('start_weight_kg');
        });

        Schema::create('weight_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('logged_on');
            $table->decimal('weight_kg', 5, 2);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'logged_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weight_logs');
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['start_weight_kg', 'target_weight_kg']);
        });
    }
};
