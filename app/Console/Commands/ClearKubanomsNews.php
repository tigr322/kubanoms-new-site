<?php

namespace App\Console\Commands;

use App\Models\Cms\CmsPage;
use App\PageType;
use Illuminate\Console\Command;

class ClearKubanomsNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kubanoms:news-clear
                            {--dry-run : Только показать количество новостей без удаления}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Удалить все страницы-новости (page_of_type = NEWS)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $newsType = PageType::NEWS->value;
        $before = CmsPage::query()
            ->where('page_of_type', $newsType)
            ->count();
        $deleted = 0;

        if (! $this->option('dry-run')) {
            $deleted = CmsPage::query()
                ->where('page_of_type', $newsType)
                ->delete();
        }

        $after = CmsPage::query()
            ->where('page_of_type', $newsType)
            ->count();

        $archivePages = CmsPage::query()
            ->whereIn('url', ['/newslist', '/newslist/'])
            ->count();

        if ($this->option('dry-run')) {
            $this->info('Режим dry-run. Данные не изменены.');
        } else {
            $this->info('Удаление новостей завершено.');
        }

        $this->line('Before: '.$before);
        $this->line('Deleted: '.$deleted);
        $this->line('After: '.$after);
        $this->line('Archive pages (/newslist): '.$archivePages);

        return self::SUCCESS;
    }
}
