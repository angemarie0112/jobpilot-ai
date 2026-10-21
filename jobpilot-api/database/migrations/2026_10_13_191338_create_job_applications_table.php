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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();

            // Application owner
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Optional relationships to existing JobPilot features
            $table->foreignId('job_description_id')
                ->nullable()
                ->constrained('job_descriptions')
                ->nullOnDelete();

            $table->foreignId('analysis_id')
                ->nullable()
                ->constrained('analyses')
                ->nullOnDelete();

            $table->foreignId('cv_id')
                ->nullable()
                ->constrained('cvs')
                ->nullOnDelete();

            $table->foreignId('tailored_cv_id')
                ->nullable()
                ->constrained('tailored_cvs')
                ->nullOnDelete();

            $table->foreignId('cover_letter_id')
                ->nullable()
                ->constrained('cover_letters')
                ->nullOnDelete();

            // Job information
            $table->string('job_title');
            $table->string('company_name');
            $table->text('job_url')->nullable();
            $table->string('location')->nullable();

            // Application progress
            $table->string('status', 50)->default('saved');

            // Important dates
            $table->date('applied_at')->nullable();
            $table->date('next_follow_up_at')->nullable();

            // Additional information
            $table->text('notes')->nullable();

            $table->timestamps();

            // Useful indexes for filtering and sorting
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'next_follow_up_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};