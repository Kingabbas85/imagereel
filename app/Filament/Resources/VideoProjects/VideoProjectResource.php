<?php

namespace App\Filament\Resources\VideoProjects;

use App\Filament\Resources\VideoProjects\Pages\CreateVideoProject;
use App\Filament\Resources\VideoProjects\Pages\EditVideoProject;
use App\Filament\Resources\VideoProjects\Pages\ListVideoProjects;
use App\Filament\Resources\VideoProjects\Schemas\VideoProjectForm;
use App\Filament\Resources\VideoProjects\Tables\VideoProjectsTable;
use App\Models\VideoProject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Resources\VideoProjectResource\Pages;
use App\Jobs\GenerateVideoJob;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VideoProjectResource extends Resource
{
    protected static ?string $model = VideoProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;
       protected static ?string $navigationLabel = 'Video Projects';

    public static function form(Schema $schema): Schema
    {
        return VideoProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VideoProjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVideoProjects::route('/'),
            'create' => CreateVideoProject::route('/create'),
            'edit' => EditVideoProject::route('/{record}/edit'),
        ];
    }

     // Sirf apne projects dikhao
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forCurrentUser();
    }
}
