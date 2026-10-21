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
        Schema::create('job_descriptions', function (Blueprint $table) {
            $table->id();

            // Each job description belongs to a user
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Basic job information
            $table->string('title');
            $table->string('company_name')->nullable();
            $table->string('location')->nullable();

            // Full job description
            $table->text('description');

            // Optional link to the original job posting
            $table->text('source_url')->nullable();

            // Creation and update timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_descriptions');
    }
};