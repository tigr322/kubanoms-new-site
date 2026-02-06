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
        $parentUrl = $this->option('parent-url');
        $parentUrl = $parentUrl !== null ? (string) $parentUrl : null;
        $updateExisting = (bool) $this->option('update-existing');

        $stats = $importer->importFromPages(
            startPage: $start,
            endPage: $end,
            baseUrl: $baseUrl,
            disk: $disk,
            imageDirectory: $imageDir,
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
        $this->line('Изображений скачано: '.$stats['images_downloaded']);
        $this->line('Изображений пропущено: '.$stats['images_skipped']);
        $this->line('Ошибок изображений: '.$stats['images_failed']);
        $this->line('Меню обновлено: '.$stats['menu_items_updated']);

        return self::SUCCESS;
    }
}
