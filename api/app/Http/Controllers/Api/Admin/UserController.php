<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListUsersRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private readonly AdminUserService $adminUserService) {}

    /**
     * Display a listing of users
     */
    public function index(ListUsersRequest $request): AnonymousResourceCollection
    {
        $users = $this->adminUserService->paginate($request->validated());

        return UserResource::collection($users);
    }

    /**
     * Display the specified user
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user->load('healthcareProfessional.institution'));
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        return new UserResource($this->adminUserService->update($user, $request->validated()));
    }

    /**
     * Remove the specified user (soft delete or suspend)
     */
    public function destroy(User $user): JsonResponse
    {
        $this->adminUserService->delete($user);

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
