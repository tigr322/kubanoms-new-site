<?php

namespace App\Http\Controllers\Admin\Oms;

use App\Http\Controllers\Controller;
use App\Models\Oms\OmsVirtualReception;
use Illuminate\Http\JsonResponse;

class VirtualReceptionAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'items' => OmsVirtualReception::with(['attachments'])->limit(50)->get(),
            'message' => 'Каркас админки виртуальной приёмной',
        ]);
    }
}
