<?php

namespace App\Filament\Resources\Cms\CmsSettings\Schemas;

use App\Filament\Resources\Cms\CmsSettings\BannerSettingHelper;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CmsSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название настройки')
                    ->required()
                    ->helperText('Уникальное имя настройки (например: contact_phone, contact_email)')
                    ->live(),
                TextInput::make('description')
                    ->label('Описание')
                    ->helperText('Краткое описание для администратора'),
                Repeater::make('banners')
                    ->label('Баннеры')
                    ->visible(fn (Get $get): bool => BannerSettingHelper::isBanner($get('name')))
                    ->schema([
                        Select::make('type')
                            ->label('Тип')
                            ->options([
                                'image' => 'Изображение',
                                'html' => 'HTML-блок',
                            ])
                            ->default('image')
                            ->required()
                            ->live(),
                        FileUpload::make('image')
                            ->label('Изображение')
                            ->disk('public')
                            ->directory(fn (Get $get): string => BannerSettingHelper::directory($get('../../name')))
                            ->visibility('public')
                            ->image()
                            ->preserveFilenames()
                            ->formatStateUsing(function ($state): array {
                                if (blank($state)) {
                                    return [];
                                }

                                if (is_array($state)) {
                                    return $state;
                                }

                                return [$state];
                            })
                            ->required(fn (Get $get): bool => ($get('type') ?? 'image') === 'image')
                            ->visible(fn (Get $get): bool => ($get('type') ?? 'image') === 'image'),
                        TextInput::make('url')
                            ->label('Ссылка')
                            ->helperText('Оставьте пустым, чтобы баннер не был кликабельным.')
                            ->visible(fn (Get $get): bool => ($get('type') ?? 'image') === 'image'),
                        TextInput::make('alt')
                            ->label('Alt-текст')
                            ->visible(fn (Get $get): bool => ($get('type') ?? 'image') === 'image'),
                        Toggle::make('open_in_new_tab')
                            ->label('Открывать в новом окне')
                            ->default(true)
                            ->visible(fn (Get $get): bool => ($get('type') ?? 'image') === 'image'),
                        Textarea::make('html')
                            ->label('HTML')
                            ->helperText('Любой HTML, например: <img src="/storage/cms/banners/image.png" alt="Баннер" />')
                            ->rows(4)
                            ->visible(fn (Get $get): bool => ($get('type') ?? 'image') === 'html'),
                    ])
                    ->defaultItems(0)
                    ->reorderable()
                    ->addActionLabel('Добавить баннер')
                    ->afterStateHydrated(function (Repeater $component, $state, Get $get): void {
                        if (filled($state)) {
                            return;
                        }

                        $content = $get('content');

                        if (! is_string($content) || $content === '') {
                            return;
                        }

                        $banners = collect(BannerSettingHelper::decodeContent($content))
                            ->map(function (array $item): array {
                                if (($item['type'] ?? 'image') !== 'image') {
                                    return $item;
                                }

                                $image = $item['image'] ?? null;

                                if (is_string($image)) {
                                    $item['image'] = [$image];
                                }

                                return $item;
                            })
                            ->all();

                        $component->state($banners);
                    }),
                Textarea::make('content')
                    ->label('Содержимое')
                    ->helperText('Значение настройки')
                    ->visible(fn (Get $get): bool => ! BannerSettingHelper::isBanner($get('name')))
                    ->columnSpanFull(),
                Toggle::make('visibility')
                    ->label('Видимость')
                    ->helperText('Показывать на сайте')
                    ->required(),
                TextInput::make('update_user')
                    ->label('Обновил')
                    ->disabled(),
                DateTimePicker::make('create_date')
                    ->label('Дата создания')
                    ->disabled(),
                TextInput::make('create_user')
                    ->label('Создал')
                    ->disabled(),
                DateTimePicker::make('update_date')
                    ->label('Дата обновления')
                    ->disabled(),
                DateTimePicker::make('delete_date')
                    ->label('Дата удаления')
                    ->disabled(),
                TextInput::make('delete_user')
                    ->label('Удалил')
                    ->disabled(),
            ]);
    }
}
