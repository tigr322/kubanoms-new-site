<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsFile;
use Illuminate\Http\JsonResponse;

class FileAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'files' => CmsFile::query()->limit(50)->get(),
            'message' => 'Каркас CRUD для файлов',
        ]);
    }
}
