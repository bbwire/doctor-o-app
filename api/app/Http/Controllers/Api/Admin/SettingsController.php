<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\SettingAuditLog;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->settingsService->getForAdmin(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->settingsService->updateFromAdmin($request->validated(), $request->user());

        return response()->json([
            'data' => $this->settingsService->getForAdmin(),
            'message' => 'Settings saved.',
        ]);
    }

    public function audit(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->input('per_page', 20), 100));
        $logs = SettingAuditLog::query()
            ->with('user:id,name,email')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
