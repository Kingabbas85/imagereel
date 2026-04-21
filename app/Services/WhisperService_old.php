<?php
// ================================================================
// FILE: app/Services/WhisperService.php
// OpenAI Whisper se audio transcribe karta hai
// ================================================================
namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Storage;

class WhisperService
{
    // Audio ko text mein convert karo — Whisper API
    public function transcribe(string $audioPath, string $language = 'ur'): array
    {
        $fullPath = storage_path('app/public/' . $audioPath);

        if (!file_exists($fullPath)) {
            throw new \Exception("Audio file nahi mili: {$audioPath}");
        }

        // OpenAI Whisper API call — yeh SRT format mein response deta hai
        $response = OpenAI::audio()->transcribe([
            'model'           => 'whisper-1',
            'file'            => fopen($fullPath, 'r'),
            'language'        => $language,   // 'ur' = Urdu
            'response_format' => 'verbose_json', // Timestamps ke sath
            'timestamp_granularities' => ['segment'], // Sentence-level timestamps
        ]);

        return [
            'text'     => $response->text,
            'segments' => $response->segments ?? [],
            'language' => $response->language ?? $language,
        ];
    }

    // Agar language detect karni ho
    public function detectLanguage(string $audioPath): string
    {
        $fullPath = storage_path('app/public/' . $audioPath);
        $response = OpenAI::audio()->transcribe([
            'model'           => 'whisper-1',
            'file'            => fopen($fullPath, 'r'),
            'response_format' => 'json',
        ]);
        return $response->language ?? 'ur';
    }
}


// ================================================================
// FILE: app/Services/SubtitleService.php
// Transcription se SRT subtitle file banata hai
// ================================================================
namespace App\Services;

class SubtitleService
{
    // SRT file generate karo — Whisper ke segments se
    public function generateSRT(array $transcription, int $projectId): string
    {
        $segments = $transcription['segments'];
        $srtContent = '';

        foreach ($segments as $index => $segment) {
            $start = $this->secondsToSRTTime($segment['start'] ?? 0);
            $end   = $this->secondsToSRTTime($segment['end'] ?? 0);
            $text  = trim($segment['text'] ?? '');

            if (empty($text)) continue;

            $srtContent .= ($index + 1) . "\n";
            $srtContent .= "{$start} --> {$end}\n";
            $srtContent .= "{$text}\n\n";
        }

        // SRT file save karo
        $srtDir  = storage_path('app/public/subtitles/');
        if (!is_dir($srtDir)) mkdir($srtDir, 0755, true);

        $srtFilename = "subtitle_{$projectId}_" . time() . ".srt";
        $srtPath     = $srtDir . $srtFilename;

        file_put_contents($srtPath, $srtContent);

        return $srtPath; // Full path return karo (FFmpeg ko chahiye)
    }

    // Seconds → SRT time format: 00:01:23,456
    private function secondsToSRTTime(float $seconds): string
    {
        $hours   = (int) ($seconds / 3600);
        $minutes = (int) (($seconds % 3600) / 60);
        $secs    = (int) ($seconds % 60);
        $ms      = (int) (($seconds - floor($seconds)) * 1000);

        return sprintf('%02d:%02d:%02d,%03d', $hours, $minutes, $secs, $ms);
    }

    // SRT ko ASS format mein convert karo — better Urdu support
    public function convertToASS(string $srtPath, array $style = []): string
    {
        $fontName  = $style['font']  ?? 'Noto Nastaliq Urdu';
        $fontSize  = $style['size']  ?? 20;
        $fontColor = $style['color'] ?? '&H00FFFFFF';

        $assHeader = "[Script Info]\nScriptType: v4.00+\n\n";
        $assHeader .= "[V4+ Styles]\nFormat: Name, Fontname, Fontsize, PrimaryColour\n";
        $assHeader .= "Style: Default,{$fontName},{$fontSize},{$fontColor}\n\n";
        $assHeader .= "[Events]\nFormat: Layer, Start, End, Style, Text\n";

        $srtContent = file_get_contents($srtPath);
        $blocks = explode("\n\n", trim($srtContent));
        $assEvents = '';

        foreach ($blocks as $block) {
            $lines = explode("\n", trim($block));
            if (count($lines) < 3) continue;

            $time  = explode(' --> ', $lines[1]);
            $start = $this->srtTimeToASS($time[0]);
            $end   = $this->srtTimeToASS($time[1]);
            $text  = implode('\N', array_slice($lines, 2));

            $assEvents .= "Dialogue: 0,{$start},{$end},Default,,{$text}\n";
        }

        $assPath = str_replace('.srt', '.ass', $srtPath);
        file_put_contents($assPath, $assHeader . $assEvents);
        return $assPath;
    }

    private function srtTimeToASS(string $time): string
    {
        return str_replace(',', '.', trim($time));
    }
}


// ================================================================
// FILE: app/Services/TTSService.php
// Text se Audio generate karta hai — OpenAI TTS
// ================================================================
namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class TTSService
{
    // Text → Audio MP3 file
    public function generate(string $text, string $voice, int $projectId): string
    {
        // OpenAI TTS API call
        $response = OpenAI::audio()->speech([
            'model'          => 'tts-1',    // tts-1-hd for better quality
            'input'          => $text,
            'voice'          => $voice,      // alloy, echo, fable, onyx, nova, shimmer
            'response_format'=> 'mp3',
        ]);

        // Audio file save karo
        $audioDir = storage_path('app/public/audio/');
        if (!is_dir($audioDir)) mkdir($audioDir, 0755, true);

        $filename = "tts_{$projectId}_" . time() . ".mp3";
        $path     = $audioDir . $filename;

        file_put_contents($path, $response->getBody());

        return 'audio/' . $filename; // Relative path return
    }
}