<?php

namespace App\Filament\Resources\Cms\CmsPages\Schemas;

use App\PageStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CmsPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->relationship('parent', 'title'),
                TextInput::make('title')
                    ->required(),
                TextInput::make('title_short'),
                TextInput::make('meta_description'),
                TextInput::make('meta_keywords'),
                DateTimePicker::make('publication_date'),
                Textarea::make('content')
                    ->columnSpanFull(),
                Select::make('page_status')
                    ->label('Статус страницы')
                    ->options([
                        1 => 'Черновик',
                        2 => 'На модерации',
                        3 => 'Опубликовано',
                    ])
                    ->required(),
                Select::make('page_of_type')
                    ->label('Тип страницы')
                    ->options([
                        1 => 'Страница',
                        2 => 'Новость',
                        7 => 'Карта сайта',
                    ])
                    ->required()
                    ->live(),
                FileUpload::make('images')
                    ->label('Фотографии')
                    ->disk('public')
                    ->directory('cms/news/images')
                    ->visibility('public')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->preserveFilenames()
                    ->maxFiles(10)
                    ->imageEditor()
                    ->columnSpanFull()
                    ->helperText('Показываются в деталях новости.')
                    ->visible(fn (Get $get): bool => (int) $get('page_of_type') === 2),
                FileUpload::make('attachments')
                    ->label('Документы')
                    ->disk('public')
                    ->directory('cms/news/attachments')
                    ->visibility('public')
                    ->multiple()
                    ->downloadable()
                    ->openable()
                    ->preserveFilenames()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/zip',
                    ])
                    ->columnSpanFull()
                    ->helperText('Прикреплённые файлы отобразятся под текстом новости.')
                    ->visible(fn (Get $get): bool => (int) $get('page_of_type') === 2),
                TextInput::make('update_user'),
                DateTimePicker::make('create_date'),
                TextInput::make('create_user'),
                DateTimePicker::make('update_date'),
                DateTimePicker::make('delete_date'),
                TextInput::make('delete_user'),
                TextInput::make('url')
                    ->label('URL страницы')
                    ->helperText('Например: /news/demo-news.html. Оставьте пустым для автогенерации из заголовка')
                    ->rule('string')
                    ->required()
                    ->rules([
                        'regex:/^\/.*/', // URL должен начинаться с /
                    ])
                    ->validationMessages([
                        'regex' => 'URL должен начинаться с символа /',
                    ]),
                TextInput::make('path'),
                TextInput::make('template'),
            ]);
    }
}
