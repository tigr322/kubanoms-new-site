<?php

namespace App\Http\Controllers;

use App\Services\PageResolverService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SitemapController extends Controller
{
    public function __construct(
        private readonly PageResolverService $pageResolverService,
    ) {}

    public function index(Request $request): Response
    {
        $layout = $this->pageResolverService->layout();

        return Inertia::render('Sitemap', [
            'menus' => $layout['menus'],
            'settings' => $layout['settings'],
            'special' => $request->cookie('special', 0),
        ]);
    }
}
