<?php

namespace App\Filament\Resources\VideoProjects\Pages;

use App\Filament\Resources\VideoProjects\VideoProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVideoProjects extends ListRecords
{
    protected static string $resource = VideoProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('+ New Video Project'),
        ];
    }
}
