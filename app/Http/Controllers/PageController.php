<?php

namespace App\Http\Controllers;

use App\Http\Requests\PageShowRequest;
use App\Models\Cms\CmsPage;
use App\Models\Cms\CmsSetting;
use App\Repositories\PageRepository;
use App\Services\PageResolverService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly PageResolverService $pageResolverService,
    ) {}

    public function show(PageShowRequest $request, string $url): Response
    {
        // Отладочный лог
        \Log::info('PageController::show called with URL: ' . $url);

        $page = $this->pageRepository->findByUrl($url);

        if (! $page) {
            abort(404);
        }

        \Log::info('Found page: ' . $page->title . ', template: ' . $page->template);

        $isAdmin = $request->user()?->role === 'admin';

        // Проверяем статус страницы с учетом enum
        $pageStatus = $page->page_status;
        if ($pageStatus instanceof \App\PageStatus) {
            // Если это enum, сравниваем с enum значением
            if ($pageStatus !== \App\PageStatus::PUBLISHED && !$isAdmin) {
                throw new ModelNotFoundException('Page unpublished');
            }
        } else {
            // Если это старое значение (int), сравниваем с числом
            if ((int) $pageStatus !== 3 && !$isAdmin) {
                throw new ModelNotFoundException('Page unpublished');
            }
        }

        // Если это страница карты сайта, используем специальную логику
        if ($page->template === 'sitemap' || $page->url === '/sitemap') {
            \Log::info('Using sitemap logic for page: ' . $page->title);
            return $this->showSitemap($request, $page);
        }

        $props = $this->pageResolverService->buildViewModel($page);

        // Добавляем контакты для главной страницы
        if ($page->url === '/') {
            $props['contacts'] = CmsSetting::getContacts();
        }

        // Определяем компонент с учетом enum
        $pageType = $page->page_of_type;
        if ($pageType instanceof \App\PageType) {
            // Если это enum, используем enum значения
            $component = match ($pageType) {
                \App\PageType::NEWS => 'NewsDetail',
                \App\PageType::SITEMAP => 'DocumentDetail', // Предполагаем, что 3 это SITEMAP
                default => 'GenericPage',
            };
        } else {
            // Если это старое значение (int), используем числовые значения
            $component = match ((int) $pageType) {
                2 => 'NewsDetail',
                3 => 'DocumentDetail',
                5 => 'PublicationDetail',
                default => 'GenericPage',
            };
        }

        return Inertia::render($component, [
            ...$props,
            'special' => $request->cookie('special', 0),
        ]);
    }

    /**
     * Показывает карту сайта
     */
    private function showSitemap(PageShowRequest $request, CmsPage $page): Response
    {
        \Log::info('showSitemap method called');

        // Получаем все видимые страницы с их дочерними страницами
        $pages = CmsPage::with(['children' => function($query) {
                $query->where('page_status', 3) // Только опубликованные
                      ->orderBy('title', 'asc');
            }])
            ->where('page_status', 3) // Только опубликованные
            ->whereNull('parent_id') // Только корневые страницы
            ->orderBy('title', 'asc')
            ->get();

        \Log::info('Found ' . $pages->count() . ' root pages');

        // Формируем структуру для карты сайта
        $link_list = $this->buildSitemapStructure($pages);

        \Log::info('Built sitemap structure with ' . count($link_list) . ' items');

        // Создаем HTML для карты сайта
        $sitemapHtml = $this->generateSitemapHtml($link_list);

        // Получаем layout данные через PageResolverService
        $layoutData = $this->pageResolverService->layout();

        \Log::info('Layout data keys: ' . implode(', ', array_keys($layoutData)));

        // Добавляем карту сайта в настройки
        $layoutData['settings']['sitemap'] = $sitemapHtml;

        $result = Inertia::render('Home', [
            'page' => [
                'title' => 'Карта сайта',
                'meta_description' => 'Карта сайта ТФОМС Краснодарского края',
                'meta_keywords' => 'карта сайта, ТФОМС, Краснодарский край'
            ],
            ...$layoutData,
            'special' => $request->cookie('special', 0),
            'latest_news' => [],
            'latest_documents' => [],
        ]);

        \Log::info('Rendering Home component with sitemap');

        return $result;
    }

    /**
     * Генерирует HTML для карты сайта
     */
    private function generateSitemapHtml(array $link_list): string
    {
        $html = '<div class="sitemap-container">';
        $html .= '<h2>Карта сайта</h2>';
        $html .= '<ul class="sitemap-list">';

        foreach ($link_list as $item) {
            $html .= $this->generateSitemapItemHtml($item);
        }

        $html .= '</ul></div>';
        return $html;
    }

    /**
     * Генерирует HTML для элемента карты сайта
     */
    private function generateSitemapItemHtml(array $item, int $level = 0): string
    {
        $html = '<li class="sitemap-item">';
        $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="sitemap-link">' . htmlspecialchars($item['title']) . '</a>';

        if (!empty($item['items'])) {
            $html .= '<ul class="sitemap-sublist">';
            foreach ($item['items'] as $subItem) {
                $html .= $this->generateSitemapItemHtml($subItem, $level + 1);
            }
            $html .= '</ul>';
        }

        $html .= '</li>';
        return $html;
    }

    /**
     * Строит древовидную структуру для карты сайта
     */
    private function buildSitemapStructure($pages): array
    {
        $link_list = [];

        foreach ($pages as $page) {
            $link_list[] = [
                'page' => $page,
                'url' => $page->url ?: '#',
                'title' => $page->title_short ?: $page->title,
                'items' => $this->buildChildrenStructure($page->children)
            ];
        }

        return $link_list;
    }

    /**
     * Рекурсивно строит структуру дочерних страниц
     */
    private function buildChildrenStructure($children): array
    {
        $items = [];

        foreach ($children as $child) {
            // Загружаем дочерние страницы для текущего ребенка
            $child->load(['children' => function($query) {
                $query->where('page_status', 3) // Только опубликованные
                      ->orderBy('title', 'asc');
            }]);

            $items[] = [
                'page' => $child,
                'url' => $child->url ?: '#',
                'title' => $child->title_short ?: $child->title,
                'items' => $this->buildChildrenStructure($child->children)
            ];
        }

        return $items;
    }
}
