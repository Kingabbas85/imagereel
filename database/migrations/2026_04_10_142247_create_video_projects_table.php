<?php

// ================================================================
// FILE: database/migrations/xxxx_create_video_projects_table.php
// COMMAND: php artisan make:migration create_video_projects_table
// Phir yeh content us file mein paste karo
// ================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_projects', function (Blueprint $table) {
            $table->id();

            // Kis user ka project hai
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Project info
            $table->string('title');
            $table->text('description')->nullable();

            // Images — JSON array of paths
            // e.g. ["images/abc.jpg", "images/xyz.jpg"]
            $table->json('image_paths')->nullable();

            // Audio file
            $table->string('audio_path')->nullable();
            $table->float('audio_duration')->nullable();

            // Text-to-Speech (agar user text likhe)
            $table->text('tts_text')->nullable();
            $table->boolean('use_tts')->default(false);
            $table->string('tts_voice')->default('alloy'); // OpenAI voice

            // Animation settings
            $table->enum('animation_type', [
                'ken_burns', 'fade', 'slide_left', 'slide_right', 'zoom_out', 'static'
            ])->default('ken_burns');

            $table->integer('image_duration')->default(8); // seconds per image

            // Subtitle settings
            $table->boolean('generate_subtitles')->default(true);
            $table->string('subtitle_language')->default('ur');
            $table->text('subtitle_text')->nullable();        // Transcribed raw text
            $table->string('subtitle_srt_path')->nullable(); // .srt file path
            $table->json('subtitle_style')->nullable();      // Font, size, color

            // Watermark / branding
            $table->string('watermark_text')->nullable();
            $table->boolean('show_end_card')->default(false);
            $table->json('end_card_data')->nullable();

            // Video resolution
            $table->string('video_resolution')->default('576x1024');

            // Output video
            $table->string('output_video_path')->nullable();
            $table->integer('video_duration')->nullable();
            $table->bigInteger('video_file_size')->nullable();

            // Processing status
            $table->enum('status', [
                'draft', 'queued', 'processing', 'completed', 'failed'
            ])->default('draft');

            $table->text('error_message')->nullable();
            $table->integer('progress_percent')->default(0);
            $table->string('current_step')->nullable(); // e.g. "Generating video..."

            // Queue info
            $table->string('job_id')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for fast queries
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_projects');
    }
};