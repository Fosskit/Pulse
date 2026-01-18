<?php

namespace App\Actions\Admin\Dashboard;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class GetDashboardStatsAction
{
    public function __invoke(): JsonResponse
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'verified' => User::whereNotNull('email_verified_at')->count(),
                'unverified' => User::whereNull('email_verified_at')->count(),
            ],
            'roles' => [
                'total' => Role::count(),
            ],
            'permissions' => [
                'total' => Permission::count(),
            ],
            'activity' => [
                'total_logs' => DB::table('activity_logs')->count(),
                'recent_logs' => DB::table('activity_logs')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(['action', 'description', 'created_at'])
                    ->map(function ($log) {
                        return [
                            'action' => $log->action,
                            'description' => $log->description,
                            'created_at' => $log->created_at,
                        ];
                    }),
            ],
        ];

        return response()->json([
            'data' => $stats,
        ]);
    }
}
