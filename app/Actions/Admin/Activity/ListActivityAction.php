<?php

namespace App\Actions\Admin\Activity;

use App\Http\Resources\ActivityResource;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListActivityAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = ActivityLog::with('user')
            ->orderBy('created_at', 'desc');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('model')) {
            $query->where('model', $request->model);
        }

        $logs = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => ActivityResource::collection($logs->items()),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
