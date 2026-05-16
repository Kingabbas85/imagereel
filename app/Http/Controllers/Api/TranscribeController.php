<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhisperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TranscribeController extends Controller
{
    public function __construct(private WhisperService $whisper) {}

    public function __invoke(Request $request)
    {
        $request->validate([
            'audio'    => 'required|file|mimes:mp3,wav,m4a,mpeg,mpga,mp4,webm,ogg|max:25600',
            'language' => 'sometimes|string|size:2',
        ]);

        $lang = $request->input('language', 'ur');
        $file = $request->file('audio');

        // Store temporarily in a private location
        $tmpPath = $file->storeAs('transcribe_tmp', uniqid('audio_', true) . '.' . $file->getClientOriginalExtension(), 'local');
        $fullPath = storage_path('app/' . $tmpPath);

        try {
            $result = $this->whisper->transcribe($fullPath, $lang);

            return response()->json([
                'text'     => $result['text'],
                'segments' => $result['segments'] ?? [],
                'language' => $result['language'] ?? $lang,
            ]);
        } catch (\Exception $e) {
            Log::error('Transcribe API error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        } finally {
            // Always delete temp file
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }
}
