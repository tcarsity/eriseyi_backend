<?php

namespace App\Http\Controllers\admin;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

    // Logins for admins and superadmin

    public function login(Request $request)
    {

        $validator = $request->validate([
            'email' => 'required|email',
        ]);



        $email = $validator['email'];
        $ip = $request->ip();



        $key = "login_attempts:{$email}:{$ip}";
        $lockoutKey = "{$key}:lockout";
        $expiresKey = "{$key}:expires_at";


        $attempts = cache()->get($key, 0);
        $isLockedOut = cache()->get($lockoutKey);



        if ($isLockedOut) {
            $expiresAt = cache()->get($expiresKey);
            $secondsLeft = $expiresAt ? max(0, $expiresAt - time()) : (4 * 60);


            return response()->json([
                'status' => 'error',
                'message' => 'Too many failed attempts. Try again later.'
            ], 429)->header('Retry-After', $secondsLeft);
        }



        // Only check user existence + role
        $user = User::where('email', $email)->first();
        if (!$user || !in_array($user->role, ['superadmin', 'admin'])) {

            $attempts++;
            cache()->put($key, $attempts, now()->addMinutes(4));



            if ($attempts >= 7) {
                cache()->put($lockoutKey, true, now()->addMinutes(4));
                cache()->put($expiresKey, time() + (4 * 60), now()->addMinutes(4));
            }


            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        // ✅ Clear rate limit
        cache()->forget($key);
        cache()->forget($lockoutKey);
        cache()->forget($expiresKey);


        // ✅ Activate user if needed
        if ($user->invite_status === 'pending') {
            $user->update([
                'invite_status' => 'active',
                'status' => 'active'
            ]);

        }

        // Generate token
        $token = $user->createToken('token')->plainTextToken;
        return (new UserResource($user))->additional([
            'message' => 'Login successful',
            'token' => $token
        ]);
    }


    public function logout(Request $request)
    {
        $user = $request->user();

        if($user){
            $user->update(['status' => 'inactive']);

            $request->user()->currentAccessToken()->delete();

            log_security_event('User logged out', [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['message' => 'Logged out successfully']);
        }

            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated.'
            ]);

    }


    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            // Only restrict if user exists
            if ($user && !in_array($user->role, ['admin', 'superadmin'])) {
                return response()->json([
                    'message' => 'If the email exists, a reset link has been sent.',
                ], 200);
            }

            $status = Password::sendResetLink(
                $request->only('email')
            );

            Log::info('Password reset attempt', [
                'email' => $request->email,
                'status' => $status,
            ]);

            log_security_event('Password reset link requested', [
                'email' => $request->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

        } catch (\Throwable $e) {
            Log::error('Password reset email failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'If the email exists, a reset link has been sent.',
        ], 200);
    }



    // public function resetPassword(Request $request)
    // {
    //     $request->validate([
    //         'token' => ['required'],
    //         'email' => ['required', 'email'],
    //         'password' => ['required', 'min:8', 'confirmed'],
    //     ]);

    //     $status = Password::reset(

    //         $request->only('email', 'password', 'password_confirmation', 'token'),

    //         function ($user) use ($request) {

    //             // Restrict role again (important)

    //             if (!in_array($user->role, ['admin', 'superadmin'])) {

    //                 abort(403, 'Unauthorized role.');

    //             }

    //             $user->forceFill([

    //                 'password' => Hash::make($request->password),

    //                 'remember_token' => Str::random(60),

    //                 'invite_status' => 'active', // ✅ Activate invite

    //             ])->save();



    //             log_security_event('Password reset successfully', [

    //                 'user_id' => $user->id,

    //                 'ip_address' => $request->ip(),

    //                 'user_agent' => $request->userAgent(),

    //             ]);

    //             event(new PasswordReset($user));

    //         }

    //     );

    //     if ($status === Password::PASSWORD_RESET) {
    //         return response()->json([
    //             'message' => 'Password reset successful.',
    //         ], 200);
    //     }

    //     return response()->json([
    //         'message' => 'Invalid token or email.',
    //     ], 400);
    // }

    public function updateAdminPassword(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8'
        ]);

        $admin = User::where('email', $request->email)->first();

        if (!$admin) {
            return response()->json([
                'message' => 'Admin not found'
            ], 404);

        }

        $admin->password = Hash::make($request->password);
        $admin->save();

        return response()->json([

            'message' => 'Password updated successfully'

        ]);

    }
}
