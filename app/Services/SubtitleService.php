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
            // Filter empty segments first
            $segments = array_values(array_filter($segments, fn($seg) => trim($seg['text'] ?? '') !== ''));

            $idx  = 1;
            $total = count($segments);
            // Group 4 segments per subtitle entry (4 lines at a time)
            for ($i = 0; $i < $total; $i += 4) {
                $group = array_slice($segments, $i, 4);

                $s    = $this->ts((float)($group[0]['start'] ?? 0));
                $e    = $this->ts((float)($group[array_key_last($group)]['end'] ?? 0));
                $text = implode("\n", array_map(fn($seg) => trim($seg['text'] ?? ''), $group));

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