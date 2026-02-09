<?php

namespace App\Console\Commands;

use App\Services\Import\KubanomsNewsImporter;
use Illuminate\Console\Command;

class ImportKubanomsNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kubanoms:import-news
                            {--start=1 : Номер первой страницы списка}
                            {--end=9 : Номер последней страницы списка}
                            {--base-url=http://kubanoms.ru : Базовый URL}
                            {--disk=public : Диск для изображений}
                            {--image-dir=cms/news/images : Подкаталог для изображений}
                            {--file-dir=cms/news/files : Подкаталог для файлов документов}
                            {--download-external-files : Скачивать внешние документы и подменять ссылки на локальные}
                            {--without-images : Не скачивать изображения}
                            {--without-documents : Не скачивать документы}
                            {--show-links : Вывести storage-ссылки загруженных файлов}
                            {--parent-url= : URL родительской страницы для новостей}
                            {--update-existing : Обновлять заголовки и метаданные}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импорт новостей kubanoms.ru из newslist';

    /**
     * Execute the console command.
     */
    public function handle(KubanomsNewsImporter $importer): int
    {
        $start = (int) $this->option('start');
        $end = (int) $this->option('end');
        $baseUrl = (string) ($this->option('base-url') ?: 'http://kubanoms.ru');
        $disk = (string) ($this->option('disk') ?: 'public');
        $imageDir = (string) ($this->option('image-dir') ?: 'cms/news/images');
        $fileDir = (string) ($this->option('file-dir') ?: 'cms/news/files');
        $downloadExternalFiles = (bool) $this->option('download-external-files');
        $downloadImages = ! (bool) $this->option('without-images');
        $downloadDocuments = ! (bool) $this->option('without-documents');
        $showLinks = (bool) $this->option('show-links');
        $parentUrl = $this->option('parent-url');
        $parentUrl = $parentUrl !== null ? (string) $parentUrl : null;
        $updateExisting = (bool) $this->option('update-existing');

        $stats = $importer->importFromPages(
            startPage: $start,
            endPage: $end,
            baseUrl: $baseUrl,
            disk: $disk,
            imageDirectory: $imageDir,
            fileDirectory: $fileDir,
            downloadExternalFiles: $downloadExternalFiles,
            downloadDocuments: $downloadDocuments,
            downloadImages: $downloadImages,
            updateExistingMeta: $updateExisting,
            parentUrl: $parentUrl,
        );

        $this->info('Импорт новостей завершен.');
        $this->line('Страниц списка: '.$stats['list_pages']);
        $this->line('Новостей найдено: '.$stats['list_items']);
        $this->line('Создано: '.$stats['pages_created']);
        $this->line('Обновлено: '.$stats['pages_updated']);
        $this->line('Дубликаты: '.$stats['duplicates_skipped']);
        $this->line('Ошибки деталей: '.$stats['details_failed']);
        $this->line('Без контента: '.$stats['details_missing']);
        $this->line('Файлов скачано: '.$stats['files_downloaded']);
        $this->line('Файлов пропущено: '.$stats['files_skipped']);
        $this->line('Ошибок файлов: '.$stats['files_failed']);
        $this->line('Storage-ссылок документов: '.count($stats['document_links'] ?? []));
        $this->line('Изображений скачано: '.$stats['images_downloaded']);
        $this->line('Изображений пропущено: '.$stats['images_skipped']);
        $this->line('Ошибок изображений: '.$stats['images_failed']);
        $this->line('Storage-ссылок изображений: '.count($stats['image_links'] ?? []));
        $this->line('Меню обновлено: '.$stats['menu_items_updated']);

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
