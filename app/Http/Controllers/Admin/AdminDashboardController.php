<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginLog;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function getStats()
    {
        $today = Carbon::today();

        $totalUsers = User::count();

        $usersByRole = User::select('role', \DB::raw('count(*) as count'))
            ->groupBy('role')
            ->get();

        $loginsToday = LoginLog::whereDate('created_at', $today)
            ->where('status', 'success')
            ->count();

        $activeUsersToday = LoginLog::whereDate('created_at', $today)
            ->where('status', 'success')
            ->distinct('user_id')
            ->count('user_id');

        $newUsers = User::where('created_at', '>=', now()->subDays(7))->get();

        $failedLogins = LoginLog::whereDate('created_at', $today)
            ->where('status', 'failed')
            ->count();

        return response()->json([
            'total_users' => $totalUsers,
            'users_by_role' => $usersByRole,
            'logins_today' => $loginsToday,
            'active_users_today' => $activeUsersToday,
            'new_users_last_7_days' => $newUsers,
            'failed_logins_today' => $failedLogins,
        ]);
    }
}
