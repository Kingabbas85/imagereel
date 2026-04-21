<?php
// FILE: app/Services/FFmpegService.php
// ✅ Private + Public dono disk se files resolve
// ✅ Ken Burns Windows stable fix
// ✅ Subtitle SRT Windows path fix
// ✅ All animations working

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FFmpegService
{
    private string $ff;
    private string $ffp;

    public function __construct()
    {
        $this->ff  = env('FFMPEG_BINARIES',  'ffmpeg');
        $this->ffp = env('FFPROBE_BINARIES', 'ffprobe');
    }

    public function generateVideo(
        array $images, ?string $audioPath, string $animationType,
        int $imageDuration, string $resolution, ?string $subtitlePath,
        ?string $watermarkText, bool $showEndCard, array $endCardData,
        int $projectId, callable $progressCallback
    ): string {

        [$w, $h] = explode('x', $resolution);
        $w = (int)$w; $h = (int)$h;

        $outDir  = storage_path('app/public/videos/');
        $tmpDir  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "reel_{$projectId}_" . time() . DIRECTORY_SEPARATOR;

        foreach ([$outDir, $tmpDir] as $d) if (!is_dir($d)) mkdir($d, 0755, true);

        $outFile  = "video_{$projectId}_" . time() . ".mp4";
        $outFull  = $outDir . $outFile;

        $progressCallback(5);

        // ── STEP 1: Images → Clips ───────────────────────────
        $clips = [];
        $total = count($images);

        foreach ($images as $i => $img) {
            $src = $this->find($img, ['images']);
            if (!$src) { Log::error("Not found: {$img}"); continue; }

            $clip   = $tmpDir . "clip_{$i}.mp4";
            $filter = $this->filter($animationType, $w, $h, $imageDuration);

            $cmd = sprintf('"%s" -loop 1 -i "%s" -vf "%s" -t %d -r 25 -c:v libx264 -preset ultrafast -pix_fmt yuv420p "%s" -y',
                $this->ff, $this->wp($src), $filter, $imageDuration, $this->wp($clip));

            $out = shell_exec($cmd . ' 2>&1');
            Log::debug("clip{$i}: " . substr($out ?? '', -150));

            if (file_exists($clip) && filesize($clip) > 100) {
                $clips[] = $clip;
                Log::info("✅ clip{$i} ok");
            } else {
                Log::error("❌ clip{$i} fail. CMD: {$cmd}");
            }

            $progressCallback(5 + (int)(($i+1)/$total*30));
        }

        if (empty($clips)) throw new \Exception("Koi clip nahi bani. Log check karo.");

        $progressCallback(37);

        // ── STEP 2: Merge ────────────────────────────────────
        $merged = $tmpDir . 'merged.mp4';

        if (count($clips) === 1) {
            copy($clips[0], $merged);
        } else {
            $lst = $tmpDir . 'list.txt';
            file_put_contents($lst, implode("\n", array_map(fn($c) => "file '" . str_replace('\\', '/', $c) . "'", $clips)));
            shell_exec(sprintf('"%s" -f concat -safe 0 -i "%s" -c copy "%s" -y', $this->ff, $this->wp($lst), $this->wp($merged)) . ' 2>&1');
            if (!file_exists($merged) || filesize($merged) < 100) copy($clips[0], $merged);
        }

        $progressCallback(50);

        // ── STEP 3: Audio ────────────────────────────────────
        $wAudio = $tmpDir . 'audio.mp4';
        $aFull  = $audioPath ? $this->find($audioPath, ['audio']) : null;

        if ($aFull) {
            shell_exec(sprintf('"%s" -i "%s" -i "%s" -map 0:v -map 1:a -c:v copy -c:a aac -b:a 128k -shortest "%s" -y',
                $this->ff, $this->wp($merged), $this->wp($aFull), $this->wp($wAudio)) . ' 2>&1');
        }
        if (!file_exists($wAudio) || filesize($wAudio) < 100) copy($merged, $wAudio);

        $progressCallback(65);

        // ── STEP 4: Subtitles ────────────────────────────────
        $wSubs = $tmpDir . 'subs.mp4';

        if ($subtitlePath && file_exists($subtitlePath)) {
            // Windows drive colon escape for FFmpeg subtitles filter
            $srt = str_replace('\\', '/', $subtitlePath);
            $srt = preg_replace('/^([A-Za-z]):/', '$1\\:', $srt);

            $cmd = sprintf('"%s" -i "%s" -vf "subtitles=\'%s\':force_style=\'FontSize=20,PrimaryColour=&H00FFFFFF,OutlineColour=&H00000000,Outline=2,Alignment=2,Bold=1\'" -c:a copy "%s" -y',
                $this->ff, $this->wp($wAudio), $srt, $this->wp($wSubs));
            $o = shell_exec($cmd . ' 2>&1');
            Log::debug("subs: " . substr($o ?? '', -200));
        }
        if (!file_exists($wSubs) || filesize($wSubs) < 100) copy($wAudio, $wSubs);

        $progressCallback(80);

        // ── STEP 5: Watermark ────────────────────────────────
        $wText = $tmpDir . 'text.mp4';
        $vf    = [];

        if ($watermarkText) {
            $t   = str_replace(["'", ':', '[', ']'], ["\\'", '\\:', '\\[', '\\]'], $watermarkText);
            $vf[] = "drawtext=text='{$t}':fontcolor=white:fontsize=20:alpha=0.9:x=w-tw-20:y=h-th-20:box=1:boxcolor=black@0.5:boxborderw=6";
        }

        if (!empty($vf)) {
            shell_exec(sprintf('"%s" -i "%s" -vf "%s" -c:a copy "%s" -y',
                $this->ff, $this->wp($wSubs), implode(',', $vf), $this->wp($wText)) . ' 2>&1');
        }
        if (!file_exists($wText) || filesize($wText) < 100) copy($wSubs, $wText);

        $progressCallback(90);

        // ── STEP 6: Final encode ─────────────────────────────
        $o = shell_exec(sprintf('"%s" -i "%s" -c:v libx264 -preset medium -crf 23 -c:a aac -b:a 128k -movflags +faststart "%s" -y',
            $this->ff, $this->wp($wText), $this->wp($outFull)) . ' 2>&1');
        Log::debug("final: " . substr($o ?? '', -150));

        if (!file_exists($outFull) || filesize($outFull) < 100) copy($wText, $outFull);
        if (!file_exists($outFull)) throw new \Exception("Final video nahi bani.");

        $this->clean($tmpDir);
        $progressCallback(100);

        return 'videos/' . $outFile;
    }

    private function filter(string $type, int $w, int $h, int $dur): string
    {
        $base   = "scale={$w}:{$h}:force_original_aspect_ratio=decrease,pad={$w}:{$h}:(ow-iw)/2:(oh-ih)/2:color=black";
        $frames = $dur * 25;
        $ws     = (int)($w * 1.35);
        $hs     = (int)($h * 1.35);

        return match($type) {
            'ken_burns' => "scale={$ws}:{$hs},zoompan=z='min(zoom+0.002,1.3)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d={$frames}:s={$w}x{$h}",
            'zoom_out'  => "scale={$ws}:{$hs},zoompan=z='if(lte(zoom,1.0),1.3,max(1.0,zoom-0.002))':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d={$frames}:s={$w}x{$h}",
            'fade'      => "{$base},fade=t=in:st=0:d=1,fade=t=out:st=" . max(1, $dur-1) . ":d=1",
            default     => $base,
        };
    }

    private function find(string $path, array $subs = []): ?string
    {
        $clean = ltrim(str_replace('\\', '/', $path), '/');
        $name  = basename($clean);

        $tries = [
            storage_path('app/private/' . $clean),
            storage_path('app/public/'  . $clean),
            $path,
        ];
        foreach ($subs as $s) {
            $tries[] = storage_path("app/private/{$s}/{$name}");
            $tries[] = storage_path("app/public/{$s}/{$name}");
        }

        foreach ($tries as $t) {
            $t = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $t);
            if (file_exists($t) && filesize($t) > 0) return $t;
        }

        foreach (['local', 'public'] as $disk) {
            try { if (Storage::disk($disk)->exists($clean)) return Storage::disk($disk)->path($clean); }
            catch (\Throwable $e) {}
        }
        return null;
    }

    private function wp(string $p): string
    {
        return PHP_OS_FAMILY === 'Windows' ? str_replace('/', '\\', $p) : $p;
    }

    public function getAudioDuration(string $audioPath): float
    {
        $f = $this->find($audioPath, ['audio']);
        if (!$f) return 0.0;
        return (float)trim(shell_exec(sprintf('"%s" -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "%s" 2>&1', $this->ffp, $this->wp($f))) ?? '0');
    }

    private function clean(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (glob($dir . '*') ?: [] as $f) if (is_file($f)) @unlink($f);
        @rmdir($dir);
    }
}
