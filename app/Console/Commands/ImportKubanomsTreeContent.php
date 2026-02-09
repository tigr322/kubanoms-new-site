<?php

namespace App\Console\Commands;

use App\Services\Import\KubanomsPageContentImporter;
use Illuminate\Console\Command;

class ImportKubanomsTreeContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kubanoms:import-tree-content
                            {--sitemap-file= : Локальный HTML-файл карты сайта}
                            {--base-url=http://kubanoms.ru : Базовый URL для ссылок и медиа}
                            {--deep=3 : Глубина рекурсивного обхода (1..3)}
                            {--disk=public : Диск для сохранения изображений}
                            {--image-dir=cms/page/images : Подкаталог для изображений}
                            {--file-dir=cms/page/files : Подкаталог для файлов документов}
                            {--download-external-files : Скачивать внешние документы и подменять ссылки на локальные}
                            {--without-images : Не скачивать изображения}
                            {--without-documents : Не скачивать документы}
                            {--show-links : Вывести storage-ссылки загруженных файлов}
                            {--limit= : Ограничить количество обрабатываемых страниц}
                            {--update-existing : Обновлять заголовки и метаданные}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Рекурсивный импорт контента страниц по sitemap-дереву (глубина 1..3)';

    /**
     * Execute the console command.
     */
    public function handle(KubanomsPageContentImporter $importer): int
    {
        $sitemapFile = (string) ($this->option('sitemap-file') ?: 'docs/Территориальный фонд ОМС Краснодарского края __ Карта сайта.html');
        $baseUrl = (string) ($this->option('base-url') ?: 'http://kubanoms.ru');
        $deep = (int) ($this->option('deep') ?: 3);
        $deep = min(3, max(1, $deep));
        $disk = (string) ($this->option('disk') ?: 'public');
        $imageDir = (string) ($this->option('image-dir') ?: 'cms/page/images');
        $fileDir = (string) ($this->option('file-dir') ?: 'cms/page/files');
        $downloadExternalFiles = (bool) $this->option('download-external-files');
        $downloadImages = ! (bool) $this->option('without-images');
        $downloadDocuments = ! (bool) $this->option('without-documents');
        $showLinks = (bool) $this->option('show-links');
        $limit = $this->option('limit');
        $limit = $limit !== null ? (int) $limit : null;
        $updateExisting = (bool) $this->option('update-existing');

        $stats = $importer->importFromSitemapTree(
            sitemapFile: $sitemapFile,
            baseUrl: $baseUrl,
            maxDepth: $deep,
            disk: $disk,
            imageDirectory: $imageDir,
            fileDirectory: $fileDir,
            downloadExternalFiles: $downloadExternalFiles,
            downloadDocuments: $downloadDocuments,
            downloadImages: $downloadImages,
            updateExistingMeta: $updateExisting,
            limit: $limit,
        );

        $this->info('Рекурсивный импорт завершен.');
        $this->line('Узлов из sitemap: '.$stats['sitemap_nodes_total']);
        $this->line('Страниц в очереди: '.$stats['pages_queued']);
        $this->line('Страниц обработано: '.$stats['pages_processed']);
        $this->line('Страниц создано: '.$stats['pages_created']);
        $this->line('Страниц обновлено: '.$stats['pages_updated']);
        $this->line('Связей parent обновлено: '.$stats['parent_links_updated']);
        $this->line('Меню обновлено: '.$stats['menu_items_updated']);
        $this->line('Страниц недоступно: '.$stats['pages_failed']);
        $this->line('Без контента: '.$stats['content_missing']);
        $this->line('Ссылок найдено в контенте: '.$stats['links_found']);
        $this->line('Ссылок добавлено в обход: '.$stats['links_queued']);
        $this->line('Файлов скачано: '.$stats['files_downloaded']);
        $this->line('Файлов пропущено: '.$stats['files_skipped']);
        $this->line('Ошибок файлов: '.$stats['files_failed']);
        $this->line('Storage-ссылок документов: '.count($stats['document_links'] ?? []));
        $this->line('Изображений скачано: '.$stats['images_downloaded']);
        $this->line('Изображений пропущено: '.$stats['images_skipped']);
        $this->line('Ошибок изображений: '.$stats['images_failed']);
        $this->line('Storage-ссылок изображений: '.count($stats['image_links'] ?? []));

        if ($showLinks) {
            $this->line('');
            $this->line('Документы:');

            foreach ($stats['document_links'] ?? [] as $link) {
                $this->line($link);
            }

            $this->line('');
            $this->line('Изображения:');

            foreach ($stats['image_links'] ?? [] as $link) {
                $this->line($link);
            }
        }

        return self::SUCCESS;
    }
}
