<?php

use App\Models\SecurityLog;
use Illuminate\Support\Facades\Auth;

if(!function_exists('log_security_event')) {
    function log_security_event($action, array $details = [])
    {

            $user = Auth::user();

            $ip =
            request()->header('X-Forwarded-For')
                ? trim(explode(',', request()->header('X-Forwarded-For'))[0])
                : request()->header('X-Real-IP') ?? request()->ip();

        SecurityLog::create([
            'user_id'    => $details['user_id'] ?? $user?->id,
            'email'      => $details['email'] ?? $user?->email,
            'action'     => $action,
            'ip_address' => $details['ip_address'] ?? $ip,
            'user_agent' => $details['user_agent'] ?? request()->userAgent(),
        ]);

    }

}
