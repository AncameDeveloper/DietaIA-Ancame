<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('age')->nullable();
            $table->enum('sex', ['male', 'female', 'other'])->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->enum('activity_level', [
                'sedentary',
                'light',
                'moderate',
                'active',
                'very_active',
            ])->default('sedentary');
            $table->enum('goal', ['lose_weight', 'maintain', 'gain_muscle'])->default('lose_weight');
            $table->unsignedSmallInteger('calorie_target')->nullable();
            $table->unsignedSmallInteger('protein_target_g')->nullable();
            $table->unsignedSmallInteger('carbs_target_g')->nullable();
            $table->unsignedSmallInteger('fat_target_g')->nullable();
            $table->json('restrictions')->nullable();
            $table->json('allergies')->nullable();
            $table->boolean('onboarding_completed')->default(false);
            $table->timestamps();
        });

        Schema::create('diet_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->json('macros_ratio')->nullable();
            $table->json('rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_diet_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diet_plan_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->string('source')->default('manual');
            $table->timestamp('started_at')->nullable();
            $table->timestamps();
        });

        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->decimal('calories', 8, 2)->default(0);
            $table->decimal('protein_g', 8, 2)->default(0);
            $table->decimal('carbs_g', 8, 2)->default(0);
            $table->decimal('fat_g', 8, 2)->default(0);
            $table->decimal('fiber_g', 8, 2)->default(0);
            $table->json('micros')->nullable();
            $table->unsignedSmallInteger('serving_g')->default(100);
            $table->timestamps();
        });

        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('eaten_on');
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snack'])->default('lunch');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->enum('source', ['manual', 'photo_ai', 'menu', 'text_ai'])->default('manual');
            $table->decimal('calories', 8, 2)->default(0);
            $table->decimal('protein_g', 8, 2)->default(0);
            $table->decimal('carbs_g', 8, 2)->default(0);
            $table->decimal('fat_g', 8, 2)->default(0);
            $table->decimal('fiber_g', 8, 2)->default(0);
            $table->json('micros')->nullable();
            $table->decimal('ai_confidence', 4, 2)->nullable();
            $table->boolean('confirmed')->default(true);
            $table->timestamps();
        });

        Schema::create('meal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_id')->nullable()->constrained('foods')->nullOnDelete();
            $table->string('name');
            $table->decimal('quantity_g', 8, 2)->default(100);
            $table->decimal('calories', 8, 2)->default(0);
            $table->decimal('protein_g', 8, 2)->default(0);
            $table->decimal('carbs_g', 8, 2)->default(0);
            $table->decimal('fat_g', 8, 2)->default(0);
            $table->json('micros')->nullable();
            $table->timestamps();
        });

        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('summary_date');
            $table->decimal('calories', 8, 2)->default(0);
            $table->decimal('protein_g', 8, 2)->default(0);
            $table->decimal('carbs_g', 8, 2)->default(0);
            $table->decimal('fat_g', 8, 2)->default(0);
            $table->decimal('fiber_g', 8, 2)->default(0);
            $table->json('micros')->nullable();
            $table->unique(['user_id', 'summary_date']);
            $table->timestamps();
        });

        Schema::create('weekly_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diet_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->date('week_start');
            $table->enum('horizon', ['daily', 'weekly'])->default('weekly');
            $table->json('content');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('request_meta')->nullable();
            $table->longText('response_raw')->nullable();
            $table->boolean('success')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_logs');
        Schema::dropIfExists('weekly_menus');
        Schema::dropIfExists('daily_summaries');
        Schema::dropIfExists('meal_items');
        Schema::dropIfExists('meals');
        Schema::dropIfExists('foods');
        Schema::dropIfExists('user_diet_assignments');
        Schema::dropIfExists('diet_plans');
        Schema::dropIfExists('profiles');
    }
};
