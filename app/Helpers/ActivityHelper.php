<?php



use App\Models\AdminActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


    if(! function_exists('log_admin_activity')) {
        function log_admin_activity(string $action, string $details = null)
        {
            try
            {


                return AdminActivity::create([
                    'user_id' => Auth::id(),
                    'action' => $action,
                    'details' => $details,
                ]);
            }catch (\Throwable $e) {
                \Log::error('Failed to log admin activity: '.$e->getMessage());
                return null;
            }
        }
    }
