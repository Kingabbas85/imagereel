<?php

namespace App\Filament\Resources\VideoProjects\Tables;

use App\Jobs\GenerateVideoJob;
use App\Models\VideoProject;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

// Table components
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;

class VideoProjectsTable
{
    // ✅ Table class mein: Table $table → Table (same rehta hai)
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Pehli image thumbnail
                ImageColumn::make('first_image')
                    ->label('')
                    ->getStateUsing(fn($record) =>
                        is_array($record->image_paths) && count($record->image_paths)
                            ? $record->image_paths[0]
                            : null
                    )
                    ->height(45)
                    ->width(45),
 
                TextColumn::make('title')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
 
                // Status badge
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match($state) {
                        'draft'      => 'gray',
                        'queued'     => 'warning',
                        'processing' => 'info',
                        'completed'  => 'success',
                        'failed'     => 'danger',
                        default      => 'gray',
                    }),
 
                TextColumn::make('current_step')
                    ->label('Step')
                    ->placeholder('—')
                    ->limit(35),
 
                TextColumn::make('progress_percent')
                    ->label('Progress')
                    ->suffix('%'),
 
                TextColumn::make('video_file_size_human')
                    ->label('Size')
                    ->getStateUsing(fn($record) => $record->video_file_size_human),
 
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'      => 'Draft',
                        'queued'     => 'Queued',
                        'processing' => 'Processing',
                        'completed'  => 'Completed ✅',
                        'failed'     => 'Failed ❌',
                    ]),
            ])
            ->actions([
                EditAction::make(),
 
                // Generate button
                Action::make('generate')
                    ->label('Generate')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    // ->visible(fn(VideoProject $record) =>
                    //     dd($record)
                    //     in_array($record->status, ['draft', 'failed'])
                    // )
                    ->requiresConfirmation()
                    ->modalHeading('Video Generate Karein?')
                    ->modalDescription('Background mein process hogi.')
                    ->action(function(VideoProject $record) {
                        $record->update([
                            'status'           => 'queued',
                            'progress_percent' => 0,
                            'error_message'    => null,
                            'current_step'     => 'Queue mein...',
                        ]);
                        GenerateVideoJob::dispatch($record);
                        Notification::make()
                            ->title('✅ Queue mein add ho gayi!')
                            ->success()
                            ->send();
                    }),
 
                // Download button
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn(VideoProject $record) => $record->isCompleted())
                    ->url(fn(VideoProject $record) => $record->video_url)
                    ->openUrlInNewTab(),
 
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('5s');
    }
}
