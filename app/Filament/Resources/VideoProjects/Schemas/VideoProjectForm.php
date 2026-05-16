<?php

namespace App\Filament\Resources\VideoProjects\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;

class VideoProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── PROJECT INFO ─────────────────────────────────────────────
                Section::make('Project Information')
                    ->icon('heroicon-o-document-text')
                    ->description('Give your video project a name and an optional description.')
                    ->compact()
                    ->schema([
                        TextInput::make('title')
                            ->label('Project Title')
                            ->placeholder('e.g. Muharram Naat 2025, Iqbal Ki Shayari')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Optional notes about this project...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // ── BACKGROUND IMAGES ────────────────────────────────────────
                Section::make('Background Images')
                    ->icon('heroicon-o-photo')
                    ->description('Upload one or more images — each image becomes a scene. Drag to reorder.')
                    ->schema([
                        FileUpload::make('image_paths')
                            ->label('Images')
                            ->helperText('JPEG, PNG, WebP — max 10 MB each — up to 10 files')
                            ->multiple()
                            ->image()
                            ->imagePreviewHeight('160')
                            ->maxFiles(10)
                            ->maxSize(10240)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->directory('images')
                            ->reorderable()
                            ->appendFiles()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                $count = count($state ?? []);
                                if ($count === 0) return;
                                $audio = $get('audio_path');
                                if (!$audio) return;
                                $fullPath = \Storage::disk('local')->path($audio);
                                if (!file_exists($fullPath)) return;
                                $ffprobe  = env('FFPROBE_BINARIES', 'ffprobe');
                                $duration = (float) trim(shell_exec(sprintf(
                                    '"%s" -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "%s" 2>&1',
                                    $ffprobe, $fullPath
                                )) ?? '0');
                                if ($duration > 0) {
                                    $set('image_duration', (int) ceil($duration / $count));
                                }
                            })
                            ->columnSpanFull(),

                        // Image cropper — shows after upload with crop-per-image buttons
                        View::make('filament.components.image-cropper')
                            ->columnSpanFull(),
                    ]),

                // ── AUDIO ────────────────────────────────────────────────────
                Section::make('Audio')
                    ->icon('heroicon-o-musical-note')
                    ->description('Upload your own audio file, or let AI generate speech from text.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('use_tts')
                            ->label('Generate Audio with AI (Text-to-Speech)')
                            ->helperText('ON → type text, AI creates the voice. OFF → upload your own file.')
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),

                        FileUpload::make('audio_path')
                            ->label('Audio File')
                            ->helperText('MP3, WAV, M4A — max 50 MB')
                            ->acceptedFileTypes(['audio/mpeg', 'audio/wav', 'audio/mp4', 'audio/x-m4a'])
                            ->maxSize(51200)
                            ->directory('audio')
                            ->visible(fn(Get $get) => ! $get('use_tts'))
                            ->required(fn(Get $get) => ! $get('use_tts'))
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state, \Livewire\Component $livewire) {
                                if (!$state) return;
                                $fullPath = \Storage::disk('local')->path($state);
                                if (!file_exists($fullPath)) return;
                                $ffprobe  = env('FFPROBE_BINARIES', 'ffprobe');
                                $duration = (float) trim(shell_exec(sprintf(
                                    '"%s" -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "%s" 2>&1',
                                    $ffprobe, $fullPath
                                )) ?? '0');
                                if ($duration > 0) {
                                    $images = $get('image_paths') ?? [];
                                    $count  = max(1, count($images));
                                    $set('image_duration', (int) ceil($duration / $count));
                                }
                                // Dispatch event for waveform cropper
                                $livewire->dispatch('audio-uploaded', path: $state);
                            })
                            ->columnSpanFull(),

                        // Waveform cropper — renders after audio upload
                        View::make('filament.components.waveform-cropper')
                            ->visible(fn(Get $get) => ! $get('use_tts'))
                            ->columnSpanFull(),

                        TextInput::make('audio_start')
                            ->label('Trim Start')
                            ->helperText('Auto-filled by waveform — or type manually')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('sec')
                            ->visible(fn(Get $get) => ! $get('use_tts')),

                        TextInput::make('audio_end')
                            ->label('Trim End')
                            ->helperText('Auto-filled by waveform — empty = full audio')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('sec')
                            ->visible(fn(Get $get) => ! $get('use_tts')),

                        Textarea::make('tts_text')
                            ->label('Text for AI Voice')
                            ->placeholder('Bismillah hir Rahman nir Raheem...')
                            ->rows(4)
                            ->visible(fn(Get $get) => $get('use_tts'))
                            ->required(fn(Get $get) => $get('use_tts'))
                            ->columnSpanFull(),

                        Select::make('tts_voice')
                            ->label('AI Voice')
                            ->options([
                                'alloy'   => '🎙️ Alloy — Neutral',
                                'echo'    => '🎙️ Echo — Male',
                                'fable'   => '🎙️ Fable — British',
                                'onyx'    => '🎙️ Onyx — Deep Male',
                                'nova'    => '🎙️ Nova — Female',
                                'shimmer' => '🎙️ Shimmer — Soft Female',
                            ])
                            ->default('alloy')
                            ->visible(fn(Get $get) => $get('use_tts')),
                    ]),

                // ── ANIMATION & VIDEO ────────────────────────────────────────
                Section::make('Animation & Video')
                    ->icon('heroicon-o-play-circle')
                    ->description('Choose how images animate and the output resolution.')
                    ->columns(3)
                    ->schema([
                        Select::make('animation_type')
                            ->label('Animation')
                            ->options([
                                'ken_burns'   => '🔍 Ken Burns — Slow Zoom In',
                                'zoom_out'    => '🔎 Zoom Out',
                                'fade'        => '✨ Fade In / Out',
                                'static'      => '🖼️ Static',
                            ])
                            ->default('ken_burns')
                            ->required(),

                        TextInput::make('image_duration')
                            ->label('Seconds Per Image')
                            ->helperText('Auto-calculated when audio is uploaded')
                            ->numeric()
                            ->default(8)
                            ->minValue(3)
                            ->maxValue(30)
                            ->suffix('sec'),

                        Select::make('video_resolution')
                            ->label('Resolution')
                            ->options([
                                '576x1024'  => '📱 576×1024 — Portrait (Reels/TikTok)',
                                '1080x1920' => '📱 1080×1920 — HD Portrait',
                                '1080x1080' => '⬜ 1080×1080 — Square',
                                '1920x1080' => '🖥️ 1920×1080 — Landscape',
                            ])
                            ->default('576x1024')
                            ->required(),
                    ]),

                // ── SUBTITLES ────────────────────────────────────────────────
                Section::make('Subtitles')
                    ->icon('heroicon-o-language')
                    ->description('Auto-generate subtitles via OpenAI Whisper. Requires a paid OpenAI API key.')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('generate_subtitles')
                            ->label('Generate Subtitles')
                            ->helperText('Whisper AI transcribes your audio into on-screen text.')
                            ->default(false)
                            ->live(),

                        Select::make('subtitle_language')
                            ->label('Language')
                            ->options([
                                'ur' => '🇵🇰 Urdu',
                                'en' => '🇬🇧 English',
                                'ar' => '🇸🇦 Arabic',
                            ])
                            ->default('ur')
                            ->visible(fn(Get $get) => $get('generate_subtitles')),
                    ]),

                // ── BRANDING ─────────────────────────────────────────────────
                Section::make('Branding & End Card')
                    ->icon('heroicon-o-tag')
                    ->description('Add a watermark and optionally show social media handles at the end.')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('watermark_text')
                            ->label('Watermark Text')
                            ->placeholder('@YourUsername'),

                        Toggle::make('show_end_card')
                            ->label('Show End Card')
                            ->default(false)
                            ->live(),

                        Repeater::make('end_card_data')
                            ->label('Social Media Links')
                            ->schema([
                                Select::make('platform')
                                    ->options([
                                        'instagram' => '📸 Instagram',
                                        'youtube'   => '▶️ YouTube',
                                        'tiktok'    => '🎵 TikTok',
                                    ])
                                    ->required(),
                                TextInput::make('handle')
                                    ->placeholder('@username')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('+ Add Platform')
                            ->visible(fn(Get $get) => $get('show_end_card'))
                            ->columnSpanFull(),
                    ]),

            ]);
    }
}
