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
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();

            // The user who requested the analysis
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // The CV being analyzed
            $table->foreignId('cv_id')
                ->constrained('cvs')
                ->restrictOnDelete();

            // The job description being analyzed
            $table->foreignId('job_description_id')
                ->constrained('job_descriptions')
                ->restrictOnDelete();

            // Analysis processing status
            $table->string('status', 30)->default('pending');

            // Indicative CV-to-job compatibility score
            $table->smallInteger('match_score')->nullable();

            // Structured AI analysis results
            $table->jsonb('matching_skills')->nullable();
            $table->jsonb('missing_skills')->nullable();
            $table->jsonb('recommendations')->nullable();

            // Overall analysis summary
            $table->text('summary')->nullable();

            // AI model information
            $table->string('model_used', 100)->nullable();

            // Error information if processing fails
            $table->text('error_message')->nullable();

            // Creation and update timestamps
            $table->timestamps();

            // Improve queries by status
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};