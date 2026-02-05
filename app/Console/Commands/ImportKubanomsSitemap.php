<?php

namespace App\Console\Commands;

use App\Services\Import\KubanomsSitemapImporter;
use Illuminate\Console\Command;

class ImportKubanomsSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kubanoms:import-sitemap
                            {--url= : URL карты сайта (по умолчанию http://kubanoms.ru/sitemap/?template=print)}
                            {--file= : Локальный HTML-файл карты сайта}
                            {--base-url=http://kubanoms.ru : Базовый URL для относительных ссылок (для файла)}
                            {--menu=SIDEBAR : Название меню (NAVBAR, SIDEBAR, CURRENT_INFORMATION)}
                            {--wipe : Удалить страницы и пункты меню перед импортом}
                            {--truncate : Очистить пункты выбранного меню перед импортом}
                            {--update-existing : Обновлять существующие страницы (title/parent)}
                            {--dry-run : Только распарсить и вывести статистику}
                            {--navbar : Дополнительно заполнить NAVBAR}
                            {--navbar-titles= : Заголовки пунктов NAVBAR через запятую}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импорт структуры сайта из карты сайта kubanoms.ru';

    /**
     * Execute the console command.
     */
    public function handle(KubanomsSitemapImporter $importer): int
    {
        $url = (string) ($this->option('url') ?: 'http://kubanoms.ru/sitemap/?template=print');
        $file = (string) ($this->option('file') ?: '');
        $baseUrl = (string) ($this->option('base-url') ?: 'http://kubanoms.ru');
        $menuName = (string) $this->option('menu');
        $wipe = (bool) $this->option('wipe');
        $dryRun = (bool) $this->option('dry-run');
        $truncate = (bool) $this->option('truncate');
        $updateExisting = (bool) $this->option('update-existing');
        $navbarTitlesRaw = (string) ($this->option('navbar-titles') ?: '');
        $withNavbar = (bool) $this->option('navbar') || $navbarTitlesRaw !== '';
        $navbarTitles = $this->parseNavbarTitles($navbarTitlesRaw);

        if ($wipe && ! $dryRun) {
            $importer->wipeStructure();
        }

        if ($file !== '') {
            $stats = $importer->importFromFile(
                filePath: $file,
                baseUrl: $baseUrl,
                menuName: $menuName,
                dryRun: $dryRun,
                truncateMenu: $truncate,
                updateExisting: $updateExisting,
            );
        } else {
            $stats = $importer->importFromUrl(
                sitemapUrl: $url,
                menuName: $menuName,
                dryRun: $dryRun,
                truncateMenu: $truncate,
                updateExisting: $updateExisting,
            );
        }

        $this->outputStats('Импорт меню '.$menuName.' завершен.', $stats);

        if ($withNavbar) {
            if ($file !== '') {
                $navStats = $importer->importFromFile(
                    filePath: $file,
                    baseUrl: $baseUrl,
                    menuName: 'NAVBAR',
                    dryRun: $dryRun,
                    truncateMenu: $truncate,
                    updateExisting: $updateExisting,
                    rootTitles: $navbarTitles,
                );
            } else {
                $navStats = $importer->importFromUrl(
                    sitemapUrl: $url,
                    menuName: 'NAVBAR',
                    dryRun: $dryRun,
                    truncateMenu: $truncate,
                    updateExisting: $updateExisting,
                    rootTitles: $navbarTitles,
                );
            }

            $this->outputStats('Импорт меню NAVBAR завершен.', $navStats);
        }

        if ($dryRun) {
            $this->warn('Dry-run: изменения в базе не применялись.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array{
     *     nodes_total: int,
     *     internal_links: int,
     *     external_links: int,
     *     file_links: int,
     *     pages_created: int,
     *     pages_existing: int,
     *     menu_items_created: int,
     *     menu_items_updated: int
     * }  $stats
     */
    private function outputStats(string $title, array $stats): void
    {
        $this->info($title);
        $this->line('Всего узлов: '.$stats['nodes_total']);
        $this->line('Внутренние ссылки: '.$stats['internal_links']);
        $this->line('Внешние ссылки: '.$stats['external_links']);
        $this->line('Файлы: '.$stats['file_links']);
        $this->line('Страницы созданы: '.$stats['pages_created']);
        $this->line('Страницы существовали: '.$stats['pages_existing']);
        $this->line('Пункты меню созданы: '.$stats['menu_items_created']);
        $this->line('Пункты меню обновлены: '.$stats['menu_items_updated']);
    }

    /**
     * @return array<int, string>
     */
    private function parseNavbarTitles(string $titles): array
    {
        if ($titles !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $titles))));
        }

        return [
            'Гражданам',
            'Медицинским организациям',
            'Страховым медицинским организациям',
            'Территориальным фондам ОМС',
        ];
    }
}
