<?php

namespace App\Http\Controllers\admin;

use App\Http\Resources\AdminActivityResource;
use App\Models\AdminActivity;
use App\Models\Event;
use App\Models\Member;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminActivityController extends Controller
{
    // Display a listing of admin activities
    public function index()
    {
        $user = Auth::user();

        $query = AdminActivity::with('user')->latest();

        if($user->role === 'admin')
        {
            $query->where('user_id', $user->id);
        }

        $activities = $query->paginate(10);

        return AdminActivityResource::collection($activities);
    }


    public function performance()
    {
        $user = Auth::user();

        if($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $eventsCount = Event::where('created_by', $user->id)->count();
        $membersCount = Member::where('created_by', $user->id)->count();

        // Use Resource for consistent formatting
        $recentActivities = AdminActivity::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

            return response()->json([
                'events_count' => $eventsCount,
                'members_count' => $membersCount,
                'data' => AdminActivityResource::collection($recentActivities),
            ]);
    }


    public function activityDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        AdminActivity::whereIn('id', $ids)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Selected activities deleted successfully',
        ], 200);

    }

}
