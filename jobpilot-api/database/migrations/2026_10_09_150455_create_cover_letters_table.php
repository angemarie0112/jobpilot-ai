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
        Schema::create('cover_letters', function (Blueprint $table) {
            $table->id();

            // The user who owns the cover letter
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // The AI analysis used to generate the cover letter
            $table->foreignId('analysis_id')
                ->constrained('analyses')
                ->cascadeOnDelete();

            // Cover letter information
            $table->string('title');

            // Generated or edited cover letter content
            $table->text('content');

            // AI model used to generate the cover letter
            $table->string('model_used', 100)->nullable();

            // Creation and update timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cover_letters');
    }
};