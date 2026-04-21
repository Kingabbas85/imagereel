<?php
// FILE: app/Services/SubtitleService.php

namespace App\Services;

class SubtitleService
{
    public function generateSRT(array $transcription, int $projectId): string
    {
        $segments = $transcription['segments'] ?? [];
        $srt      = '';

        if (empty($segments) && !empty($transcription['text'])) {
            $srt = "1\n00:00:00,000 --> 00:00:05,000\n" . trim($transcription['text']) . "\n\n";
        } else {
            $idx = 1;
            foreach ($segments as $seg) {
                $s    = $this->ts((float)($seg['start'] ?? 0));
                $e    = $this->ts((float)($seg['end']   ?? 0));
                $text = trim($seg['text'] ?? '');
                if (!$text) continue;
                $srt .= "{$idx}\n{$s} --> {$e}\n{$text}\n\n";
                $idx++;
            }
        }

        $dir = storage_path('app/private/subtitles/');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $path = $dir . "sub_{$projectId}_" . time() . ".srt";
        file_put_contents($path, $srt);

        return $path;
    }

    private function ts(float $s): string
    {
        return sprintf('%02d:%02d:%02d,%03d',
            (int)($s / 3600),
            (int)(($s % 3600) / 60),
            (int)($s % 60),
            (int)(($s - floor($s)) * 1000)
        );
    }
}