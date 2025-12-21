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
            'password' => 'required'
        ]);

        $email = $validator['email'];
        $ip = $request->ip();

        // Rate limit key
        $key = "login_attempts:{$email}:{$ip}";
        $lockoutKey = "{$key}:lockout";
        $expiresKey = "{$key}:expires_at";

        // check if locked out
        $attempts = cache()->get($key, 0);
        $isLockedOut = cache()->get($lockoutKey);

        if($isLockedOut)
        {
            $expiresAt = cache()->get($expiresKey);
            $secondsLeft = $expiresAt ? max(0, $expiresAt - time()) : (4 * 60);

            return response()->json([
                'status' => 'error',
                'message' => 'Too many failed attempts. Try again in 4 minutes.'
            ], 429)->header('Retry-After', $secondsLeft);
        }

        // find user
        $user = User::where('email', $validator['email'])->first();

        // check invalid login
        if(
            ! $user ||
            ! Hash::check($validator['password'], $user->password) ||
            ! in_array($user->role, [ 'superadmin', 'admin'])
        ) {

            // Increase failed attempts
            $attempts++;
            cache()->put($key, $attempts, now()->addMinutes(4));

            //lockout at 7 minutes
            if($attempts >= 7)
            {
                cache()->put($lockoutKey, true, now()->addMinutes(4));
                cache()->put($expiresKey, time() + (4 * 60), now()->addMinutes(4));
            }

                log_security_event('Failed login attempt', [
                    'email' => $validator['email'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'attempts' => $attempts,
                ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'attempts' => $attempts,
                'remaining' => max(0, 7 - $attempts)
            ], 401);
        }

        // successfully login
        cache()->forget($key);
        cache()->forget($lockoutKey);
        cache()->forget($expiresKey);

        $user->update(['status' => 'active']);

        $token = $user->createToken('token')->plainTextToken;

        log_security_event('User logged in', [
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return (new UserResource($user))->additional([
            'message' => 'Login Successfully',
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
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Only admin & superadmin can request reset
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json([
                'message' => 'Unauthorized role.',
            ], 403);
        }

        try{
            Log::info('Attempting password reset email', [
                'email' => $request->email,
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
            ]);


            $status = Password::sendResetLink([
                'email' => $request->email,
            ]);

            Log::info('Password reset mail status', [
                'email' => $request->email,
                'status' => $status,
            ]);


            log_security_event('Password reset link requested', [
                'email' => $request->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

        }catch (\Throwable $e) {
                Log::error('Password reset email failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Mail service error. Please contact support.',
            ], 500);
        }


        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent successfully.',
            ], 200);
        }

        Log::warning('Password reset link not sent', [
            'email' => $request->email,
            'status' => $status,
        ]);

        return response()->json([
            'message' => 'Unable to send reset link. Please try again.',
        ], 400);
    }


    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {

                // Restrict role again (important)
                if (!in_array($user->role, ['admin', 'superadmin'])) {
                    abort(403, 'Unauthorized role.');
                }

                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                log_security_event('Password reset successfully', [
                    'user_id' => $user->id,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password reset successful.',
            ], 200);
        }

        return response()->json([
            'message' => 'Invalid token or email.',
        ], 400);
    }
}
