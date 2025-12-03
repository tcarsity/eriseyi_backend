<?php

namespace App\Http\Controllers;

use App\Http\Resources\SecurityLogResource;
use App\Models\SecurityLog;
use Illuminate\Http\Request;

class SecurityLogController extends Controller
{
    public function index()
    {
        $logs = SecurityLog::with('user')->latest()->paginate(5);
        return SecurityLogResource::collection($logs);
    }

    public function bulkDelete(Request $request)
    {

        $ids = $request->input('ids', []);

        SecurityLog::whereIn('id', $ids)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Selected logs deleted successfully',
        ], 200);
    }
}
