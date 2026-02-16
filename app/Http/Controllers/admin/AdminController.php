<?php

namespace App\Http\Controllers\admin;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;
use App\Helpers\SupabaseHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    //Get all admins
    public function index()
    {
        $admins = User::where('role', 'admin')->paginate(10);
        return UserResource::collection($admins);
    }


    public function store(Request $request)
    {

        $validated = $request->validate([

            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|string'

        ]);

        $validated['password'] = Hash::make($validated['password']);

        $admin = User::create($validated);

        // 🔥 Invite to Supabase

        $response = SupabaseHelper::invite($admin->email);

        if (!$response->successful()) {

            \Log::error('Supabase invite failed', [

                'email' => $admin->email,

                'response' => $response->body(),

            ]);

        }
        return (new UserResource($admin))

            ->additional(['message' => 'Admin added successfully and invite sent']);

    }


    public function show(User $user)
    {
        return new UserResource($user);
    }


    public function update(Request $request, User $user)
    {

        $validated = $request->validate([

            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:users,email,' . $user->id,

        ]);

        $emailChanged = isset($validated['email'])

            && $validated['email'] !== $user->email;

        $user->update($validated);

        // 🔥 Sync Supabase if email changed

        if ($emailChanged) {
            SupabaseHelper::updateEmail($user->email, $validated['email']);
        }
        return (new UserResource($user))

            ->additional(['message' => 'Admin updated successfully']);

    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'Admin deleted successfully']);
    }


    public function changePassword(Request $request)
    {
        $user = auth()->user();

       $validator =  $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
       ]);

        if(!Hash::check($validator['current_password'], $user->password)){
            return response()->json([
                'errors' => [
                    'current_password' => ['Incorrect password']
                    ]
                ], 422);
        }

        log_security_event('Password Changed', [
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $user->update([
            'password' => Hash::make($validator['new_password']),
        ]);

        return (new UserResource($user))->additional(['message' => 'Password updated successfully']);
    }

    public function searchAdmin(Request $request)
    {
       $query = User::query();

       $query->where('role', 'admin');

       if($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'LIKE', "%{$search}%");
       }

       $admins = $query->latest()->paginate(10);

       return UserResource::collection($admins);
    }


    public function activeAdmins()
    {
        $admins = User::where('role', 'admin')->get();

        $admins = $admins->map(function ($admin) {
            $admin->is_active = $admin->last_seen
                ? Carbon::parse($admin->last_seen)->gte(now()->subSeconds(60))
                : false;

            return $admin;
        });

        return response()->json([
            'data' => UserResource::collection($admins),
        ]);
    }


    public function updateProfile(Request $request)
    {
        $user = $request->user();

        log_security_event('Profile updated', [
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $user->update($request->only(['name', 'email']));

        return response()->json([
            'status' => 'success',
            'message' => 'Profile Updated Successfully',
            'data' => new UserResource($user),
        ], 200);
    }
}
