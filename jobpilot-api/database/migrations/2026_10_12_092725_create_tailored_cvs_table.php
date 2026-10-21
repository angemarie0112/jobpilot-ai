<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tailored_cvs', function (Blueprint $table) {
            $table->id();

            // User who owns this tailored CV.
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Original uploaded CV.
            $table->foreignId('cv_id')
                ->constrained('cvs')
                ->cascadeOnDelete();

            // Match analysis used to generate this CV.
            $table->foreignId('analysis_id')
                ->constrained('analyses')
                ->cascadeOnDelete();

            // User-friendly name.
            $table->string('title');

            // Structured CV sections stored as JSON.
            $table->json('content');

            // AI model used to generate the tailored version.
            $table->string('model_used')->nullable();

            $table->timestamps();

            // Improve queries for each user's tailored CVs.
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tailored_cvs');
    }
};