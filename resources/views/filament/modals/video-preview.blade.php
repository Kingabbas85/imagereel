<div class="space-y-4 p-1">

    {{-- Video Player --}}
    @if ($record->isCompleted() && $record->video_url)
        <div class="flex justify-center">
            <video
                controls
                autoplay
                loop
                playsinline
                class="rounded-xl shadow-lg max-h-[70vh]"
                style="max-width: 100%;"
                src="{{ $record->video_url }}"
            >
                Your browser does not support video playback.
            </video>
        </div>

        <div class="flex items-center justify-between text-sm text-gray-400 px-1">
            <span>{{ $record->video_resolution }} • {{ ucfirst($record->animation_type ?? '') }}</span>
            <span>{{ $record->video_file_size_human }}</span>
        </div>

    @elseif ($record->audio_path)
        {{-- Audio-only preview when video not ready --}}
        <div class="flex flex-col items-center gap-4 py-6">
            <div class="w-16 h-16 rounded-full bg-violet-600/20 flex items-center justify-center">
                <svg class="w-8 h-8 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                </svg>
            </div>
            <p class="text-gray-400 text-sm">Video abhi generate nahi hui — audio preview:</p>
            <audio controls class="w-full" src="{{ route('admin.preview.audio') . '?path=' . urlencode($record->audio_path) }}">
                Your browser does not support audio playback.
            </audio>
        </div>

    @else
        <div class="flex flex-col items-center gap-3 py-10 text-gray-500">
            <svg class="w-12 h-12 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M15 10l4.553-2.069A1 1 0 0121 8.82V15.18a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
            </svg>
            <p class="text-sm">Koi preview available nahi — pehle Generate karo.</p>
        </div>
    @endif

    {{-- Project info --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-3 grid grid-cols-2 gap-2 text-xs text-gray-500">
        <span><strong class="text-gray-400">Status:</strong> {{ ucfirst($record->status) }}</span>
        <span><strong class="text-gray-400">Progress:</strong> {{ $record->progress_percent }}%</span>
        @if($record->current_step)
            <span class="col-span-2"><strong class="text-gray-400">Step:</strong> {{ $record->current_step }}</span>
        @endif
    </div>
</div>
