<?php

namespace App\Http\Controllers\admin;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Member;
use App\Models\Testimonial;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SuperAdminDashboardController extends Controller
{
    //


    public function getStats(Request $request)
    {
        $user = $request->user();

        $data = [
            'members' => ['count' => Member::count()],
            'testimonials' => ['count' => Testimonial::latest()->take(4)->count()],
        ];

        // New members trend (last 7 days)
        $trend = Member::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $thisWeekCount = Member::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ])->count();

        $lastWeekCount = Member::whereBetween('created_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek(),
        ])->count();

        $growth = $thisWeekCount - $lastWeekCount;

        $data['newMembers'] = [
            'count' => $trend->sum('count'),
            'trend' => $trend,
            'thisweek' => $thisWeekCount,
            'lastweek' => $lastWeekCount,
            'growth' => $growth,
        ];

        if ($user->role === 'superadmin') {

            //Only admins that are logged in
            $admins = User::where('role', 'admin')
            ->whereNotNull('last_seen')
            ->get();


            $activeAdmins = $admins->filter(function ($admin) {
                return Carbon::parse($admin->last_seen)
                    ->gte(now()->subSeconds(60));
            });

            $data['admins'] = [
                'data' => UserResource::collection($admins),
                'count' => $admins->count(),
                'active' => $activeAdmins->count(),
                'inactive' => $admins->count() - $activeAdmins->count(),
            ];

            $data['admin_activity'] = User::where('role', 'admin')
                ->withCount([
                    'members as members_created',
                    'events as events_created'
                ])
                ->get(['id', 'name']);
        }

        return response()->json($data);
    }

}
