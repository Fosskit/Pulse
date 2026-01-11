<?php

namespace App\Actions\Auth;

use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefreshTokenAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Revoke old token
        $request->user()->token()->revoke();
        
        // Create new token
        $token = $user->createToken('Personal Access Token')->accessToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }
}
