<?php

namespace App\Filament\Resources\Cms\CmsPages\Schemas;

use App\Models\Cms\CmsPage;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                RichEditor::make('content')
                    ->label('Контент')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('cms/page/attachments')
                    ->fileAttachmentsVisibility('public')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                        ['table', 'attachFiles'],
                        ['undo', 'redo'],
                    ])
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
                        3 => 'Документ',
                        5 => 'Публикация',
                        7 => 'Карта сайта',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                        $template = match ((int) $state) {
                            2 => 'news',
                            3 => 'document',
                            5 => 'publication',
                            7 => 'sitemap',
                            default => null,
                        };

                        if ($template) {
                            $set('template', $template);
                        }
                    }),
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
                    ->directory(fn (Get $get): string => (int) $get('page_of_type') === 2
                        ? 'cms/news/attachments'
                        : 'cms/documents/attachments')
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
                    ->visible(fn (Get $get): bool => in_array((int) $get('page_of_type'), [2, 3], true)),
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
                TextInput::make('template')
                    ->label('Шаблон')
                    ->datalist(function (): array {
                        $knownTemplates = [
                            'default',
                            'home',
                            'news',
                            'document',
                            'publication',
                            'sitemap',
                            'vr',
                        ];

                        $dbTemplates = CmsPage::query()
                            ->select('template')
                            ->whereNotNull('template')
                            ->where('template', '!=', '')
                            ->distinct()
                            ->orderBy('template')
                            ->pluck('template')
                            ->all();

                        return collect([...$knownTemplates, ...$dbTemplates])
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();
                    })
                    ->helperText('Подсказки: default, home, news, document, publication, sitemap, vr.'),
            ]);
    }
}
