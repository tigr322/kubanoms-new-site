<?php

namespace App\Filament\Resources\Cms\CmsFiles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CmsFileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('file_folder_id')
                    ->label('Папка')
                    ->relationship('folder', 'title')
                    ->searchable()
                    ->preload(),
                FileUpload::make('upload')
                    ->label('Загрузить файл')
                    ->storeFiles(false)
                    ->maxSize(51200)
                    ->columnSpanFull()
                    ->helperText('Поддерживаются любые файлы. После сохранения ссылка будет доступна как /storage/...'),
                TextInput::make('original_name')
                    ->label('Имя файла')
                    ->required(fn (Get $get): bool => blank($get('upload'))),
                TextInput::make('path')
                    ->label('Путь')
                    ->helperText('Например: cms/files/my-file.pdf. Если загружаете файл выше, заполнится автоматически.'),
                TextInput::make('storage_url')
                    ->label('Storage ссылка')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Готовая ссылка для баннеров и контента.'),
                TextInput::make('mime_type')
                    ->label('MIME')
                    ->required(fn (Get $get): bool => blank($get('upload'))),
                TextInput::make('extension')
                    ->label('Расширение')
                    ->required(fn (Get $get): bool => blank($get('upload'))),
                TextInput::make('description')
                    ->label('Описание'),
                DateTimePicker::make('create_date')
                    ->disabled(),
                TextInput::make('create_user')
                    ->disabled(),
                DateTimePicker::make('update_date')
                    ->disabled(),
                TextInput::make('update_user')
                    ->disabled(),
                DateTimePicker::make('delete_date'),
                TextInput::make('delete_user'),
            ]);
    }
}
