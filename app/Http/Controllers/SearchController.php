<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Services\PageResolverService;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __construct(
        private readonly PageResolverService $pageResolverService,
    ) {}

    public function index(SearchRequest $request): Response
    {
        $term = trim((string) $request->validated('q', ''));
        $layout = $this->pageResolverService->layout();

        $results = $this->searchResults($term, $layout['menus'] ?? []);

        return Inertia::render('Search', [
            'query' => $term,
            'results' => $results,
            'menus' => $layout['menus'],
            'settings' => $layout['settings'],
            'special' => $request->cookie('special', 0),
        ]);
    }

    /**
     * @param  array<string, mixed>  $menus
     * @return array<int, array{id: string, title: string, url: string, excerpt: string}>
     */
    private function searchResults(string $term, array $menus): array
    {
        if ($term === '') {
            return [];
        }

        $menuResults = collect()
            ->merge($this->menuResults($menus['navbar'] ?? [], $term))
            ->merge($this->menuResults($menus['sidebar'] ?? [], $term))
            ->merge($this->menuResults($menus['current_information'] ?? [], $term));

        $resultsByUrl = $menuResults
            ->filter(fn (array $item): bool => $item['url'] !== '')
            ->keyBy('url');

        $pageResults = $this->pageResolverService
            ->search($term, 50)
            ->map(function ($item): array {
                $title = $this->safeString($item->title ?? '');
                $url = (string) ($item->url ?? '');

                $content = $this->safeString($item->content ?? '');
                $excerpt = str($content)
                    ->stripTags()
                    ->squish()
                    ->limit(180)
                    ->toString();

                return [
                    'id' => 'page-'.$item->id,
                    'title' => $title,
                    'url' => $url,
                    'excerpt' => $excerpt,
                ];
            })
            ->filter(fn (array $item): bool => $item['url'] !== '');

        foreach ($pageResults as $result) {
            if (! $resultsByUrl->has($result['url'])) {
                $resultsByUrl->put($result['url'], $result);
            }
        }

        return $resultsByUrl
            ->values()
            ->take(50)
            ->all();
    }

    /**
     * @param  iterable<int, mixed>  $items
     * @param  array<int, string>  $trail
     * @return Collection<int, array{id: string, title: string, url: string, excerpt: string}>
     */
    private function menuResults(iterable $items, string $term, array $trail = []): Collection
    {
        $results = collect();

        foreach ($items as $item) {
            /** @var array{id?: mixed, title?: mixed, url?: mixed, children?: mixed} $item */
            $title = $this->safeString((string) ($item['title'] ?? ''));
            $url = is_string($item['url'] ?? null) ? (string) $item['url'] : '';

            $currentTrail = array_values(array_filter([...$trail, $title]));
            $path = implode(' → ', $currentTrail);

            $haystack = $title.' '.$path.' '.$url;

            if ($url !== '' && $this->containsCaseInsensitive($haystack, $term)) {
                $id = 'menu-'.(string) ($item['id'] ?? md5($url));

                $results->push([
                    'id' => $id,
                    'title' => $title,
                    'url' => $url,
                    'excerpt' => $path !== '' ? 'Меню: '.$path : 'Меню',
                ]);
            }

            $children = $item['children'] ?? [];
            $results = $results->merge($this->menuResults($children, $term, $currentTrail));
        }

        return $results;
    }

    private function containsCaseInsensitive(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        return mb_stripos($haystack, $needle) !== false;
    }

    private function safeString(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $converted = @iconv('Windows-1251', 'UTF-8//IGNORE', $value);

        if (is_string($converted) && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        $stripped = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

        return is_string($stripped) ? $stripped : '';
    }
}
