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
        Schema::create('cvs', function (Blueprint $table) {
            $table->id();

            // Each CV belongs to a registered user
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Uploaded PDF information
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');

            // Extracted content from the PDF
            $table->text('extracted_text')->nullable();

            // Processing information
            $table->string('status', 30)->default('uploaded');
            $table->text('error_message')->nullable();

            // Creation and update timestamps
            $table->timestamps();

            // Improve queries that filter CVs by processing status
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cvs');
    }
};