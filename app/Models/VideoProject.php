<?php

// ================================================================
// FILE: app/Models/VideoProject.php
// COMMAND: php artisan make:model VideoProject
// Phir yeh content paste karo
// ================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VideoProject extends Model Implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'user_id', 'title', 'description',
        'image_paths', 'audio_path', 'audio_duration', 'audio_start', 'audio_end',
        'tts_text', 'use_tts', 'tts_voice',
        'animation_type', 'image_duration',
        'generate_subtitles', 'subtitle_language',
        'subtitle_text', 'subtitle_srt_path', 'subtitle_style',
        'watermark_text', 'show_end_card', 'end_card_data',
        'video_resolution', 'output_video_path',
        'video_duration', 'video_file_size',
        'status', 'error_message', 'progress_percent', 'current_step',
        'job_id', 'processing_started_at', 'processing_completed_at',
    ];

    protected $casts = [
        'image_paths'             => 'array',
        'subtitle_style'          => 'array',
        'end_card_data'           => 'array',
        'generate_subtitles'      => 'boolean',
        'use_tts'                 => 'boolean',
        'show_end_card'           => 'boolean',
        'audio_duration'          => 'float',
        'audio_start'             => 'float',
        'audio_end'               => 'float',
        'progress_percent'        => 'integer',
        'image_duration'          => 'integer',
        'video_file_size'         => 'integer',
        'processing_started_at'   => 'datetime',
        'processing_completed_at' => 'datetime',
    ];

    // ── RELATIONSHIPS ─────────────────────────────────────────
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registerMediaConversions(?Media $media = null): void
{
    $this
        ->addMediaConversion('preview')
        ->fit(Fit::Contain, 300, 300)
        ->nonQueued();
}

    // ── ACCESSORS ─────────────────────────────────────────────
    public function getVideoUrlAttribute(): ?string
    {
        if (!$this->output_video_path) return null;
        return Storage::url($this->output_video_path);
    }

    public function getVideoFileSizeHumanAttribute(): string
    {
        if (!$this->video_file_size) return '—';
        return round($this->video_file_size / 1048576, 2) . ' MB';
    }

    // ── HELPERS ───────────────────────────────────────────────
    public function isCompleted(): bool  { return $this->status === 'completed'; }
    public function isFailed(): bool     { return $this->status === 'failed'; }
    public function isProcessing(): bool { return in_array($this->status, ['queued', 'processing']); }

    public function updateProgress(int $percent, string $step = null, string $status = null): void
    {
        $data = ['progress_percent' => $percent];
        if ($step)   $data['current_step'] = $step;
        if ($status) $data['status']        = $status;
        $this->update($data);
    }

    public static function statusColor(string $status): string
    {
        return match($status) {
            'draft'      => 'gray',
            'queued'     => 'warning',
            'processing' => 'info',
            'completed'  => 'success',
            'failed'     => 'danger',
            default      => 'gray',
        };
    }

    // ── SCOPES ────────────────────────────────────────────────
    public function scopeForCurrentUser($query)
    {
        return $query->where('user_id', auth()->id());
    }
}