<?php

namespace App\Filament\Resources\Cms\CmsPages\Schemas;

use App\Models\Cms\CmsFile;
use App\Models\Cms\CmsFileFolder;
use App\Models\Cms\CmsPage;
use App\Services\Cms\CmsFileUploadService;
use App\Support\Cms\ContentStorageLinkPresenter;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

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
                Textarea::make('content_raw_html')
                    ->label('HTML контента (таблицы / video)')
                    ->dehydrated(false)
                    ->rows(14)
                    ->columnSpanFull()
                    ->helperText('Если в визуальном редакторе не видны ссылки в <video>, редактируйте HTML здесь. При изменении это поле обновляет контент страницы.')
                    ->afterStateHydrated(function (Set $set, ?CmsPage $record): void {
                        if (! $record instanceof CmsPage) {
                            return;
                        }

                        $rawContent = $record->getRawOriginal('content');

                        if (is_string($rawContent)) {
                            $set('content_raw_html', $rawContent);
                        }
                    })
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                        if (! is_string($state)) {
                            return;
                        }

                        $set('content', $state);
                    }),
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
                    ->live()
                    ->columnSpanFull()
                    ->helperText('Необязательно. Если поле пустое, на главной в ленте будет использоваться первая картинка из контента (если есть).')
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
                    ->live()
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
                Placeholder::make('content_storage_links')
                    ->label('Файлы и ссылки для вставки в контент')
                    ->columnSpanFull()
                    ->content(fn (Get $get, ?CmsPage $record): HtmlString => self::buildStorageLinksContent($get, $record)),
                Actions::make([
                    Action::make('upload_cms_file')
                        ->label('Загрузить документ')
                        ->modalHeading('Загрузить файл в CMS')
                        ->schema([
                            Select::make('file_folder_id')
                                ->label('Папка')
                                ->options(fn (): array => CmsFileFolder::query()
                                    ->orderBy('title')
                                    ->pluck('title', 'id')
                                    ->mapWithKeys(fn (string $title, int|string $id): array => [(string) $id => $title])
                                    ->all())
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
                                ->helperText('Готовая ссылка будет доступна после сохранения.'),
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
                        ])
                        ->action(function (array $data): void {
                            $normalized = app(CmsFileUploadService::class)->normalizeForSave(
                                data: $data,
                                folderId: null,
                                actor: Filament::auth()->user()?->name,
                                isCreate: true,
                            );

                            CmsFile::query()->create($normalized);
                        })
                        ->successNotificationTitle('Файл загружен'),
                ])
                    ->label('Быстрая загрузка файлов')
                    ->columnSpanFull(),
                Placeholder::make('cms_files_registry')
                    ->label('Все файлы CMS (копия списка cms-files)')
                    ->columnSpanFull()
                    ->content(fn (): HtmlString => self::buildCmsFilesRegistryContent()),
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

    private static function buildStorageLinksContent(Get $get, ?CmsPage $record): HtmlString
    {
        $presenter = app(ContentStorageLinkPresenter::class);
        $images = $get('images');
        $attachments = $get('attachments');
        $editorContent = is_string($get('content')) ? $get('content') : null;
        $recordContent = null;

        if ($record instanceof CmsPage) {
            $rawContent = $record->getRawOriginal('content');
            $recordContent = is_string($rawContent) ? $rawContent : null;
        }

        $contentForPreview = $editorContent;

        $editorStorageLinks = $presenter->collect(
            images: $images,
            attachments: $attachments,
            content: $editorContent,
        );
        $editorExternalMp4Links = $presenter->collectExternalVideoLinks($editorContent);

        $usedRecordFallback = false;

        if ($editorStorageLinks === [] && $editorExternalMp4Links === [] && is_string($recordContent) && $recordContent !== '') {
            $contentForPreview = $recordContent;
            $usedRecordFallback = true;
        }

        $plainLinks = $presenter->buildPlainText(
            images: $images,
            attachments: $attachments,
            content: $contentForPreview,
        );

        $htmlSnippets = $presenter->buildHtmlSnippets(
            images: $images,
            attachments: $attachments,
            content: $contentForPreview,
        );

        $externalVideoLinks = $presenter->buildExternalVideoPlainText($contentForPreview);

        $fallbackHint = $usedRecordFallback
            ? '<p style="margin:0;color:#b45309;font-size:12px;">Показаны ссылки из сохранённого HTML страницы (редактор может скрывать теги &lt;video&gt; до сохранения).</p>'
            : '';

        $html = '<div style="display:grid;gap:12px;">
            <div>
                <div style="font-weight:600;margin-bottom:6px;">Storage-ссылки</div>
                <textarea readonly rows="6" style="width:100%;font-family:monospace;font-size:12px;padding:8px;border:1px solid #d1d5db;border-radius:6px;">'.e($plainLinks).'</textarea>
            </div>
            <div>
                <div style="font-weight:600;margin-bottom:6px;">Готовые HTML-вставки</div>
                <textarea readonly rows="8" style="width:100%;font-family:monospace;font-size:12px;padding:8px;border:1px solid #d1d5db;border-radius:6px;">'.e($htmlSnippets).'</textarea>
            </div>
            <div>
                <div style="font-weight:600;margin-bottom:6px;">Внешние видео-ссылки из контента (mp4)</div>
                <textarea readonly rows="6" style="width:100%;font-family:monospace;font-size:12px;padding:8px;border:1px solid #d1d5db;border-radius:6px;">'.e($externalVideoLinks).'</textarea>
            </div>
            <p style="margin:0;color:#6b7280;font-size:12px;">
                Блок собирает ссылки из "Фотографии", "Документы", уже вставленных в контент путей /storage/... и отдельно показывает внешние mp4-ссылки.
            </p>
            '.$fallbackHint.'
        </div>';

        return new HtmlString($html);
    }

    private static function buildCmsFilesRegistryContent(): HtmlString
    {
        $files = CmsFile::query()
            ->select([
                'id',
                'original_name',
                'path',
                'mime_type',
                'extension',
                'update_date',
            ])
            ->orderByDesc('id')
            ->get();

        $presenter = app(ContentStorageLinkPresenter::class);

        $html = '<div style="display:grid;gap:10px;">
            <p style="margin:0;color:#374151;font-size:12px;">
                Список ниже дублирует <a href="/admin/cms/cms-files" target="_blank" rel="noopener">/admin/cms/cms-files</a>. 
                В поле Storage URL можно сразу копировать ссылку для вставки в контент.
            </p>'
            .$presenter->buildCmsFilesTableHtml($files)
            .'</div>';

        return new HtmlString($html);
    }
}
