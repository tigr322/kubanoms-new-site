<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Services\PageResolverService;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __construct(
        private readonly PageResolverService $pageResolverService,
    ) {}

    public function index(SearchRequest $request): Response
    {
        $term = (string) $request->validated('q', '');
        $results = $term !== ''
            ? $this->pageResolverService->search($term)
            : collect();

        $layout = $this->pageResolverService->layout();

        return Inertia::render('Search', [
            'query' => $term,
            'results' => $results->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'url' => $item->url,
                'excerpt' => str($item->content ?? '')->limit(180),
            ]),
            'menus' => $layout['menus'],
            'settings' => $layout['settings'],
            'special' => $request->cookie('special', 0),
        ]);
    }
}
