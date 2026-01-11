<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyEmailAction
{
    public function __invoke(Request $request, string $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ], 400);
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'message' => 'Invalid verification link.',
            ], 400);
        }

        if ($user->markEmailAsVerified()) {
            return response()->json([
                'message' => 'Email verified successfully.',
            ]);
        }

        return response()->json([
            'message' => 'Unable to verify email.',
        ], 400);
    }
}
