<?php

namespace App\Filament\Resources\VideoProjects\Pages;

use App\Filament\Resources\VideoProjects\VideoProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVideoProject extends CreateRecord
{
    protected static string $resource = VideoProjectResource::class;

    // Form submit hone se pehle user_id set karo
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
