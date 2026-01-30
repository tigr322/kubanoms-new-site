<?php

namespace App\Http\Controllers;

use App\Repositories\PageRepository;
use App\Services\PageResolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly PageResolverService $pageResolverService,
    ) {}

    public function index(Request $request): Response
    {
        $page = $this->pageRepository->findHome()
            ?? $this->pageRepository->findByUrl('/');

        if (! $page) {
            abort(404);
        }

        $latest = $this->pageResolverService->latestNewsAndDocuments();

        return Inertia::render('Home', [
            ...$this->pageResolverService->buildViewModel($page),
            'latest_news' => $latest['news']->map($this->transformListItem())->values(),
            'latest_documents' => $latest['documents']->map($this->transformListItem())->values(),
            'special' => $request->cookie('special', 0),
        ]);
    }

    private function transformListItem(): callable
    {
        return static fn ($item): array => [
            'id' => $item->id,
            'title' => $item->title,
            'url' => $item->url,
            'date' => optional($item->publication_date)?->format('d.m.Y'),
            'path' => $item->path,
            'image' => self::normalizeMediaPath(
                collect($item->images ?? [])->filter()->first(),
            ),
            'images' => collect($item->images ?? [])
                ->filter()
                ->map(fn (string $path): string => self::normalizeMediaPath($path) ?? '')
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private static function normalizeMediaPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $clean = $path;

        if (preg_match('#https?://[^/]+(/storage/.*)$#', $path, $matches)) {
            $clean = $matches[1];
        }

        if (Str::startsWith($clean, '//')) {
            return '/'.ltrim($clean, '/');
        }

        if (Str::startsWith($clean, '/storage/')) {
            return $clean;
        }

        $normalized = preg_replace('#^public/#', '', ltrim($clean, '/'));

        return '/storage/'.$normalized;
    }
}
