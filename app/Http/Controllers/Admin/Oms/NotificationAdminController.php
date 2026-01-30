<?php

namespace App\Http\Controllers\Admin\Oms;

use App\Http\Controllers\Controller;
use App\Models\Oms\OmsNotificationMoIncluded;
use App\Models\Oms\OmsNotificationSmoChange;
use App\Models\Oms\OmsNotificationSmoIncluded;
use App\Models\Oms\OmsNotificationSmoOutput;
use Illuminate\Http\JsonResponse;

class NotificationAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'mo_included' => OmsNotificationMoIncluded::limit(20)->get(),
            'smo_included' => OmsNotificationSmoIncluded::limit(20)->get(),
            'smo_change' => OmsNotificationSmoChange::limit(20)->get(),
            'smo_output' => OmsNotificationSmoOutput::limit(20)->get(),
            'message' => 'Каркас админки уведомлений',
        ]);
    }
}
