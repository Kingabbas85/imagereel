<?php

// ================================================================
// FILE 4: app/Filament/Widgets/StatsOverviewWidget.php
// COMMAND: php artisan make:filament-widget StatsOverviewWidget --stats-overview
// ================================================================
namespace App\Filament\Widgets;
 
use App\Models\VideoProject;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
 
class StatsOverviewWidget extends BaseWidget
{
    // protected static ?string $pollingInterval = '5s'; // Auto refresh
 
    protected function getStats(): array
    {
        $userId = auth()->id();
 
        $total      = VideoProject::where('user_id', $userId)->count();
        $completed  = VideoProject::where('user_id', $userId)->where('status', 'completed')->count();
        $processing = VideoProject::where('user_id', $userId)->whereIn('status', ['queued', 'processing'])->count();
        $failed     = VideoProject::where('user_id', $userId)->where('status', 'failed')->count();
 
        return [
            Stat::make('Total Projects', $total)
                ->icon('heroicon-o-film')
                ->color('gray'),
 
            Stat::make('✅ Completed', $completed)
                ->description('Download ke liye ready')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
 
            Stat::make('⏳ Processing', $processing)
                ->description('Abhi ban rahi hain')
                ->icon('heroicon-o-arrow-path')
                ->color('warning'),
 
            Stat::make('❌ Failed', $failed)
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
 