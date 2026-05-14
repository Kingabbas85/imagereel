<?php

namespace App\Filament\Widgets;

use App\Models\VideoProject;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected ?string $pollingInterval = '5s';

    protected function getStats(): array
    {
        $userId = auth()->id();

        $total      = VideoProject::where('user_id', $userId)->count();
        $completed  = VideoProject::where('user_id', $userId)->where('status', 'completed')->count();
        $processing = VideoProject::where('user_id', $userId)->whereIn('status', ['queued', 'processing'])->count();
        $failed     = VideoProject::where('user_id', $userId)->where('status', 'failed')->count();

        return [
            Stat::make('Total Projects', $total)
                ->description('All time')
                ->descriptionIcon('heroicon-o-folder')
                ->icon('heroicon-o-film')
                ->color('gray'),

            Stat::make('Completed', $completed)
                ->description('Ready to download')
                ->descriptionIcon('heroicon-o-check-circle')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Processing', $processing)
                ->description($processing > 0 ? 'In queue or rendering…' : 'None active')
                ->descriptionIcon($processing > 0 ? 'heroicon-o-arrow-path' : 'heroicon-o-check')
                ->icon('heroicon-o-arrow-path')
                ->color($processing > 0 ? 'warning' : 'gray'),

            Stat::make('Failed', $failed)
                ->description($failed > 0 ? 'Click Generate to retry' : 'No failures')
                ->descriptionIcon($failed > 0 ? 'heroicon-o-exclamation-circle' : 'heroicon-o-check')
                ->icon('heroicon-o-x-circle')
                ->color($failed > 0 ? 'danger' : 'gray'),
        ];
    }
}
