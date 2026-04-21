<?php

// ================================================================
// FILE: app/Filament/Resources/VideoProjects/Schemas/VideoProjectForm.php
//
// ✅ FILAMENT 5 SAHI TARIKA:
//   - configure() ka argument  → Filament\Schemas\Schema
//   - Section, Grid            → Filament\Schemas\Components\Section/Grid
//   - TextInput, FileUpload    → Filament\Forms\Components\... (same rahte hain)
//   - Get (reactive)           → Filament\Schemas\Components\Utilities\Get
//
// ❌ JO GALAT THA:
//   - configure(Form $form): Form   ← yeh v3 style tha
//   - Filament\Forms\Get            ← yeh v3 style tha
// ================================================================

namespace App\Filament\Resources\VideoProjects\Schemas;

use Filament\Schemas\Schema;                                    // ✅ Filament 5
use Filament\Schemas\Components\Section;                        // ✅ Filament 5
use Filament\Schemas\Components\Utilities\Get;                  // ✅ Filament 5

// Form fields — yeh same rehte hain Filament 3/4/5 mein
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;

class VideoProjectForm
{
    // ✅ SAHI: Schema $schema → Schema
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ════════════════════════════════════════════
                // SECTION 1 — PROJECT INFO
                // ════════════════════════════════════════════
                Section::make('📋 Project Information')
                    ->description('Project ka naam dalein')
                    ->schema([
                        TextInput::make('title')
                            ->label('Project Title')
                            ->placeholder('e.g. Muharram Naat 2025, Iqbal Ki Shayari')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description (Optional)')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // ════════════════════════════════════════════
                // SECTION 2 — IMAGE UPLOAD
                // ════════════════════════════════════════════
                Section::make('🖼️ Background Images')
                    ->description('Ek ya zyada images — har image ek scene banega')
                    ->schema([
                        FileUpload::make('image_paths')
                            ->label('Images Upload Karein')
                            ->helperText('JPEG, PNG, WebP — max 10MB each — drag to reorder')
                            ->multiple()
                            ->image()
                            ->imagePreviewHeight('180')
                            ->maxFiles(10)
                            ->maxSize(10240)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->directory('images')
                            ->reorderable()
                            ->appendFiles()
                            ->required()
                            ->columnSpanFull(),
                    ]),

                // ════════════════════════════════════════════
                // SECTION 3 — AUDIO
                // ════════════════════════════════════════════
                Section::make('🎵 Audio')
                    ->description('Upload karo ya text likho — AI se audio generate hogi')
                    ->columns(2)
                    ->schema([
                        Toggle::make('use_tts')
                            ->label('Text se Audio Generate Karein? (AI TTS)')
                            ->helperText('ON = Text likho → AI se audio. OFF = Apni audio upload karo.')
                            ->default(false)
                            ->live()                                     // ✅ Filament 5: reactive() → live()
                            ->columnSpanFull(),

                        // Audio upload — sirf jab use_tts OFF ho
                        FileUpload::make('audio_path')
                            ->label('Audio File Upload Karein')
                            ->helperText('MP3, WAV, M4A — max 50MB')
                            ->acceptedFileTypes(['audio/mpeg', 'audio/wav', 'audio/mp4', 'audio/x-m4a'])
                            ->maxSize(51200)
                            ->directory('audio')
                            ->visible(fn(Get $get) => ! $get('use_tts'))
                            ->required(fn(Get $get) => ! $get('use_tts'))
                            ->columnSpanFull(),

                        // TTS text — sirf jab use_tts ON ho
                        Textarea::make('tts_text')
                            ->label('Yahan Text Likho — AI Audio Banega')
                            ->placeholder('Bismillah hir Rahman nir Raheem...')
                            ->rows(4)
                            ->visible(fn(Get $get) => $get('use_tts'))
                            ->required(fn(Get $get) => $get('use_tts'))
                            ->columnSpanFull(),

                        Select::make('tts_voice')
                            ->label('AI Voice Choose Karein')
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

                // ════════════════════════════════════════════
                // SECTION 4 — ANIMATION SETTINGS
                // ════════════════════════════════════════════
                Section::make('🎬 Animation Settings')
                    ->columns(3)
                    ->schema([
                        Select::make('animation_type')
                            ->label('Animation Type')
                            ->options([
                                'ken_burns'   => '🔍 Ken Burns — Slow Zoom',
                                'fade'        => '✨ Fade In/Out',
                                'slide_left'  => '⬅️ Slide Left',
                                'slide_right' => '➡️ Slide Right',
                                'zoom_out'    => '🔎 Zoom Out',
                                'static'      => '🖼️ Static',
                            ])
                            ->default('ken_burns')
                            ->required(),

                        TextInput::make('image_duration')        // ✅ TextInput — FileUpload nahi
                            ->label('Seconds Per Image')
                            ->numeric()
                            ->default(8)
                            ->minValue(3)
                            ->maxValue(30)
                            ->suffix('sec'),

                        Select::make('video_resolution')
                            ->label('Video Size')
                            ->options([
                                '576x1024'  => '📱 576x1024 — Portrait (TikTok/Reels)',
                                '1080x1920' => '📱 1080x1920 — HD Portrait',
                                '1080x1080' => '⬜ 1080x1080 — Square',
                                '1920x1080' => '🖥️ 1920x1080 — Landscape',
                            ])
                            ->default('576x1024')
                            ->required(),
                    ]),

                // ════════════════════════════════════════════
                // SECTION 5 — SUBTITLES
                // ════════════════════════════════════════════
                Section::make('📝 Subtitles')
                    ->columns(2)
                    ->schema([
                        Toggle::make('generate_subtitles')
                            ->label('Urdu Subtitles Generate Karein?')
                            ->helperText('Whisper AI se audio transcribe hogi')
                            ->default(true)
                            ->live(),                                    // ✅ reactive() → live()

                        Select::make('subtitle_language')
                            ->label('Subtitle Language')
                            ->options([
                                'ur' => '🇵🇰 Urdu',
                                'en' => '🇬🇧 English',
                                'ar' => '🇸🇦 Arabic',
                            ])
                            ->default('ur')
                            ->visible(fn(Get $get) => $get('generate_subtitles')),
                    ]),

                // ════════════════════════════════════════════
                // SECTION 6 — BRANDING
                // ════════════════════════════════════════════
                Section::make('🏷️ Branding')
                    ->columns(2)
                    ->schema([
                        TextInput::make('watermark_text')
                            ->label('Watermark (Optional)')
                            ->placeholder('@YourUsername'),

                        Toggle::make('show_end_card')
                            ->label('End Card Dikhao?')
                            ->default(false)
                            ->live(),                                    // ✅ reactive() → live()

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