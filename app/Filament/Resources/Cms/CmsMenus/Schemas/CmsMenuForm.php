<?php

namespace App\Filament\Resources\Cms\CmsMenus\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CmsMenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('max_depth')
                    ->required()
                    ->numeric(),
                Repeater::make('rootItems')
                    ->label('Структура меню')
                    ->relationship(name: 'rootItems')
                    ->orderColumn('sort_order')
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->schema([
                        TextInput::make('title')
                            ->label('Название')
                            ->required()
                            ->live(onBlur: true),
                        Toggle::make('visible')
                            ->label('Показывать')
                            ->default(true),
                        Select::make('page_id')
                            ->label('Страница')
                            ->relationship('page', 'title')
                            ->searchable()
                            ->preload(),
                        TextInput::make('url')
                            ->label('URL (если внешняя ссылка)')
                            ->url(),
                        Repeater::make('children')
                            ->label('Подпункты')
                            ->relationship('children')
                            ->orderColumn('sort_order')
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Название')
                                    ->required()
                                    ->live(onBlur: true),
                                Toggle::make('visible')
                                    ->label('Показывать')
                                    ->default(true),
                                Select::make('page_id')
                                    ->label('Страница')
                                    ->relationship('page', 'title')
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('url')
                                    ->label('URL (если внешняя ссылка)')
                                    ->url(),
                                Repeater::make('children')
                                    ->label('Подпункты')
                                    ->relationship('children')
                                    ->orderColumn('sort_order')
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Название')
                                            ->required()
                                            ->live(onBlur: true),
                                        Toggle::make('visible')
                                            ->label('Показывать')
                                            ->default(true),
                                        Select::make('page_id')
                                            ->label('Страница')
                                            ->relationship('page', 'title')
                                            ->searchable()
                                            ->preload(),
                                        TextInput::make('url')
                                            ->label('URL (если внешняя ссылка)')
                                            ->url(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
                TextInput::make('update_user'),
                TextInput::make('title')
                    ->required(),
                DateTimePicker::make('create_date'),
                TextInput::make('create_user'),
                DateTimePicker::make('update_date'),
                DateTimePicker::make('delete_date'),
                TextInput::make('delete_user'),
            ]);
    }
}
