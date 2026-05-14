<?php
// ╔══════════════════════════════════════════════════════════════╗
// ║  FILE: app/Jobs/GenerateVideoJob.php                        ║
// ║                                                              ║
// ║  FIX: set_time_limit(0) — Job mein timeout hatao            ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Jobs;

use App\Models\VideoProject;
use App\Services\FFmpegService;
use App\Services\SubtitleService;
use App\Services\TTSService;
use App\Services\WhisperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 1;

    public function __construct(private VideoProject $project) {}

    public function handle(
        FFmpegService $ffmpeg, WhisperService $whisper,
        SubtitleService $subtitle, TTSService $tts
    ): void {

        // ✅ FIX: PHP max_execution_time ko 0 karo (no limit)
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        try {
            $this->step(5, 'processing', '🎬 Shuru ho raha hai...');

            // ── STEP 1: TTS ───────────────────────────────────
            $audioPath = $this->project->audio_path;

            if ($this->project->use_tts && $this->project->tts_text) {
                $this->step(10, 'processing', '🔊 AI se audio generate ho rahi hai...');

                $audioPath = $tts->generate(
                    $this->project->tts_text,
                    $this->project->tts_voice ?? 'alloy',
                    $this->project->id
                );

                $this->project->update(['audio_path' => $audioPath]);
                $this->step(20, 'processing', '✅ Audio ready!');
            }

            // ── STEP 2: Whisper Subtitles ─────────────────────
            $srtPath = null;

            if ($this->project->generate_subtitles && $audioPath) {
                $this->step(25, 'processing', '📝 Audio transcript ho rahi hai...');
                sleep(1);

                $trans   = $whisper->transcribe($audioPath, $this->project->subtitle_language ?? 'ur');
                $srtPath = $subtitle->generateSRT($trans, $this->project->id);

                $this->project->update([
                    'subtitle_text'     => $trans['text'],
                    'subtitle_srt_path' => $srtPath,
                ]);

                $this->step(50, 'processing', '✅ Subtitles ready!');
            }

            // ── STEP 3: Video ─────────────────────────────────
            $this->step(55, 'processing', '🎞️ Video ban rahi hai...');

            $images = $this->project->image_paths ?? [];
            if (empty($images)) {
                throw new \Exception('Koi image nahi mili. Form se dobara submit karo.');
            }

            $out = $ffmpeg->generateVideo(
                images:           $images,
                audioPath:        $audioPath,
                animationType:    $this->project->animation_type    ?? 'static',
                imageDuration:    (int)($this->project->image_duration ?? 8),
                resolution:       $this->project->video_resolution  ?? '576x1024',
                subtitlePath:     $srtPath,
                watermarkText:    $this->project->watermark_text,
                showEndCard:      (bool)$this->project->show_end_card,
                endCardData:      $this->project->end_card_data ?? [],
                projectId:        $this->project->id,
                progressCallback: function (int $p) {
                    $m = 55 + (int)($p * 0.4);
                    $this->step($m, 'processing', "🎞️ Render: {$m}%");
                },
                audioStart:       (float)($this->project->audio_start ?? 0),
                audioEnd:         $this->project->audio_end ? (float)$this->project->audio_end : null,
            );

            // ── STEP 4: Complete ──────────────────────────────
            $full = storage_path('app/public/' . $out);

            $this->project->update([
                'status'                  => 'completed',
                'progress_percent'        => 100,
                'current_step'            => '✅ Video ready! Download karo.',
                'output_video_path'       => $out,
                'video_file_size'         => file_exists($full) ? filesize($full) : 0,
                'processing_completed_at' => now(),
                'error_message'           => null,
            ]);

            Log::info("✅ Done #{$this->project->id} → {$out}");

        } catch (\Throwable $e) {
            $msg = $e->getMessage();

            if (str_contains(strtolower($msg), 'rate limit')) {
                $msg = 'OpenAI rate limit. 1 minute baad dobara Generate karo.';
            } elseif (str_contains($msg, 'API key')) {
                $msg = 'OpenAI API key nahi hai. .env mein OPENAI_API_KEY set karo.';
            }

            $this->project->update([
                'status'        => 'failed',
                'current_step'  => '❌ ' . mb_substr($msg, 0, 200),
                'error_message' => mb_substr($msg, 0, 500),
            ]);

            Log::error("❌ #{$this->project->id}: " . $e->getMessage());
        }
    }

    private function step(int $pct, string $status, string $msg): void
    {
        $this->project->update([
            'progress_percent' => $pct,
            'status'           => $status,
            'current_step'     => $msg,
        ]);
    }
}