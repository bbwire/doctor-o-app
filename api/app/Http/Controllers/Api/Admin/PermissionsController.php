<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminPermission;
use Illuminate\Http\JsonResponse;

class PermissionsController extends Controller
{
    /**
     * List all admin permission keys and labels (for UI when assigning permissions).
     */
    public function index(): JsonResponse
    {
        $labels = AdminPermission::labels();
        $data = array_map(fn (string $key) => [
            'key' => $key,
            'label' => $labels[$key] ?? $key,
        ], AdminPermission::all());

        return response()->json(['data' => array_values($data)]);
    }
}
