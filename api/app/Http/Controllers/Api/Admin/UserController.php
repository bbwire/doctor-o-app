<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListUsersRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AdminUserService;
use App\Services\AuditLogService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $adminUserService,
        private readonly WalletService $walletService
    ) {}

    /**
     * Display a listing of users
     */
    public function index(ListUsersRequest $request): AnonymousResourceCollection
    {
        $users = $this->adminUserService->paginate($request->validated());

        return UserResource::collection($users);
    }

    /**
     * Store a newly created user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->adminUserService->create($request->validated());
        app(AuditLogService::class)->log(
            $request->user(),
            'user.created',
            'Created user: ' . ($user->name ?? $user->email),
            User::class,
            $user->id,
            ['email' => $user->email, 'role' => $user->role ?? null]
        );
        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
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
        $updated = $this->adminUserService->update($user, $request->validated());
        app(AuditLogService::class)->log(
            $request->user(),
            'user.updated',
            'Updated user: ' . ($updated->name ?? $updated->email),
            User::class,
            $updated->id,
            ['email' => $updated->email]
        );
        return new UserResource($updated);
    }

    /**
     * Remove the specified user (soft delete or suspend)
     */
    public function destroy(User $user): JsonResponse
    {
        $name = $user->name ?? $user->email ?? '#' . $user->id;
        $id = $user->id;
        $this->adminUserService->delete($user);
        app(AuditLogService::class)->log(
            request()->user(),
            'user.deleted',
            'Deleted user: ' . $name,
            User::class,
            $id,
            ['email' => $user->email ?? null]
        );
        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    /**
     * Top-up wallet credit for a patient (for testing / before payment integration).
     */
    public function topUp(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999'],
        ]);
        $amount = (float) $request->input('amount');

        $transaction = $this->walletService->topUp($user, $amount);
        app(AuditLogService::class)->log(
            $request->user(),
            'wallet.top_up',
            'Topped up wallet for patient: ' . ($user->name ?? $user->email) . ' – ' . number_format($amount, 0) . ' UGX',
            User::class,
            $user->id,
            ['amount' => $amount, 'balance_after' => (float) $transaction->balance_after]
        );
        return response()->json([
            'message' => 'Credit added successfully.',
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => (float) $transaction->amount,
                'balance_after' => (float) $transaction->balance_after,
            ],
        ], 200);
    }
}
