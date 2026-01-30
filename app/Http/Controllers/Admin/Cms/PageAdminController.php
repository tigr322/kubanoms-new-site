<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsPage;
use Illuminate\Http\JsonResponse;

class PageAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'pages' => CmsPage::query()->limit(25)->get(),
            'message' => 'Каркас CRUD для страниц (Filament/Inertia подключить позже)',
        ]);
    }
}
