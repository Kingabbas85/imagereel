<?php

namespace App\Filament\Resources\VideoProjects\Pages;

use App\Filament\Resources\VideoProjects\VideoProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVideoProject extends EditRecord
{
    protected static string $resource = VideoProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
