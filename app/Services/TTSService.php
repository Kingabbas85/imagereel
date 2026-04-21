<?php
// ╔══════════════════════════════════════════════════════════════╗
// ║  FILE: app/Services/TTSService.php                          ║
// ║                                                              ║
// ║  FIX: set_time_limit(0) — PHP timeout remove karo           ║
// ║  FIX: Private disk mein save karo                           ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class TTSService
{
    public function generate(string $text, string $voice, int $projectId): string
    {
        // ✅ FIX 1: PHP timeout hatao — TTS time leta hai
        set_time_limit(0);

        $dir = storage_path('app/private/audio/');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $file = "tts_{$projectId}_" . time() . ".mp3";
        $full = $dir . $file;

        $attempts = 0;

        while ($attempts < 3) {
            try {
                $attempts++;
                Log::info("TTS attempt {$attempts}: voice={$voice}, len=" . strlen($text));

                $response = OpenAI::audio()->speech([
                    'model'           => 'tts-1',
                    'input'           => $text,
                    'voice'           => $voice,
                    'response_format' => 'mp3',
                ]);

                file_put_contents($full, $response->getBody());
                Log::info("TTS saved: {$full}");

                return 'audio/' . $file;

            } catch (\Exception $e) {
                $isLimit = str_contains(strtolower($e->getMessage()), 'rate limit')
                        || str_contains($e->getMessage(), '429');

                if ($isLimit && $attempts < 3) {
                    Log::warning("TTS rate limit. 30s wait...");
                    sleep(30);
                    continue;
                }

                throw $e;
            }
        }

        throw new \Exception("TTS 3 attempts ke baad bhi fail.");
    }
}