<?php
// FILE: app/Services/WhisperService.php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhisperService
{
    public function transcribe(string $audioPath, string $language = 'ur'): array
    {
        set_time_limit(0);

        $fullPath = $this->resolve($audioPath);

        if (!$fullPath) {
            throw new \Exception("Audio file nahi mili: {$audioPath}");
        }

        Log::info("Whisper: {$fullPath}");

        if (filesize($fullPath) / 1048576 > 25) {
            throw new \Exception("Audio 25MB se bari hai.");
        }

        $attempts = 0;

        while ($attempts < 3) {
            try {
                $attempts++;

                $response = OpenAI::audio()->transcribe([
                    'model'                   => 'whisper-1',
                    'file'                    => fopen($fullPath, 'r'),
                    'language'                => $language,
                    'response_format'         => 'verbose_json',
                    'timestamp_granularities' => ['segment'],
                ]);

                return [
                    'text'     => $response->text,
                    'segments' => $response->segments ?? [],
                    'language' => $response->language ?? $language,
                ];

            } catch (\Exception $e) {
                $isLimit = str_contains(strtolower($e->getMessage()), 'rate limit')
                        || str_contains($e->getMessage(), '429');

                if ($isLimit && $attempts < 3) {
                    Log::warning("Whisper rate limit. 60s wait...");
                    sleep(60);
                    continue;
                }

                throw $e;
            }
        }

        throw new \Exception("Whisper 3 attempts ke baad fail.");
    }

    private function resolve(string $path): ?string
    {
        $clean = ltrim(str_replace('\\', '/', $path), '/');
        $name  = basename($clean);

        $tries = [
            storage_path('app/private/' . $clean),
            storage_path('app/private/audio/' . $name),
            storage_path('app/public/'  . $clean),
            storage_path('app/public/audio/'  . $name),
            $path,
        ];

        foreach ($tries as $t) {
            $t = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $t);
            if (file_exists($t) && filesize($t) > 0) return $t;
        }

        foreach (['local', 'public'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($clean)) {
                    return Storage::disk($disk)->path($clean);
                }
            } catch (\Throwable $e) {}
        }

        Log::error("Audio not found: {$path}");
        return null;
    }
}