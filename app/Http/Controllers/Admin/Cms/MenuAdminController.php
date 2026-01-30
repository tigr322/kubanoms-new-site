<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsMenu;
use Illuminate\Http\JsonResponse;

class MenuAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'menus' => CmsMenu::with('items')->get(),
            'message' => 'Каркас CRUD для меню',
        ]);
    }
}
