<?php

namespace App\Actions\Admin\Users;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class DeleteUserAction
{
    public function __invoke(User $user): JsonResponse
    {
        // Log activity before deletion
        app(\App\Services\ActivityLogService::class)->log(
            action: 'user.deleted',
            model: 'User',
            modelId: $user->id,
            description: "User '{$user->name}' was deleted"
        );

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
