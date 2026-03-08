<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\ActivityLog;
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
        $logs = ActivityLog::query()
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $data = collect($logs->items())->map(function (ActivityLog $log) {
            $props = $log->properties ?? [];
            return [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user,
                'user_id' => $log->user_id,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'properties' => $props,
                'created_at' => $log->created_at?->toISOString(),
                // For settings.updated, expose key/old_value/new_value for table columns
                'key' => $props['key'] ?? null,
                'old_value' => $props['old_value'] ?? null,
                'new_value' => $props['new_value'] ?? null,
            ];
        })->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
