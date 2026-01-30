<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsSetting;
use Illuminate\Http\JsonResponse;

class SettingAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'settings' => CmsSetting::query()->get(),
            'message' => 'Каркас CRUD для настроек',
        ]);
    }
}
