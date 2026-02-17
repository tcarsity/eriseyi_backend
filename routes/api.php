<?php


use App\Http\Controllers\admin\AuthController;
use App\Http\Controllers\admin\AdminController;
use App\Http\Controllers\admin\EventController;
use App\Http\Controllers\front\MemberController;
use App\Http\Controllers\SecurityLogController;
use App\Http\Controllers\admin\SuperAdminDashboardController;
use App\Http\Controllers\admin\AdminActivityController;
use App\Http\Controllers\admin\TestimonialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;


Route::post('/members/public', [MemberController::class, 'publicStore']);
Route::post('/login', [AuthController::class, 'login']);


Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/update-admin-password', [AuthController::class, 'updateAdminPassword']);

// Testimonial public route
Route::get('/public-testimonials', [TestimonialController::class, 'publicTestimonials']);

//event public routes
Route::get('/public-events', [EventController::class, 'publicEvents']);





Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/heartbeat', function () {
        $user = auth()->user();

        if ($user) {
            $user->update([
                'last_seen' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    });

    Route::post('/logout', [AuthController::class, 'logout']);




    Route::middleware('role:superadmin')->group(function () {
        Route::apiResource('users', AdminController::class);
        Route::get('/superadmin/search-admins', [AdminController::class, 'searchAdmin']);
        Route::get('/admin-status', [AdminController::class, 'activeAdmins']);
        Route::get('/security-logs', [SecurityLogController::class, 'index']);
        Route::post('/security-logs/bulk-delete', [SecurityLogController::class, 'bulkDelete']);
        Route::post('/admin-activities/bulk-delete', [AdminActivityController::class, 'activityDelete']);
        Route::post('/users/{user}/resend-invite', [AdminController::class, 'resendInvite']);
    });

    Route::middleware('role:superadmin,admin')->group(function () {
        Route::get('/dashboard-stats', [SuperAdminDashboardController::class, 'getStats']);

        // Members Controller route
        Route::get('/members/search', [MemberController::class, 'searchMember']);
        Route::get('/members', [MemberController::class, 'index']);
        Route::post('/members', [MemberController::class, 'store']);
        Route::get('/members/{member}', [MemberController::class, 'show']);
        Route::put('/members/{member}', [MemberController::class, 'update']);
        Route::delete('/members/{member}', [MemberController::class, 'destroy']);
        Route::post('/members/admin', [MemberController::class, 'adminStore']);
        Route::get('/admin/recent-members', [MemberController::class, 'recentPublicMembers']);

        // Testimonial Controller route
        Route::get('/testimonials', [TestimonialController::class, 'index']);
        Route::post('/testimonials', [TestimonialController::class, 'store']);
        Route::get('/testimonials/{testimonial}', [TestimonialController::class, 'show']);
        Route::put('/testimonials/{testimonial}', [TestimonialController::class, 'update']);
        Route::delete('/testimonials/{testimonial}', [TestimonialController::class, 'destroy']);

        // Event Controller route
        Route::get('/events', [EventController::class, 'index']);
        Route::post('/events', [EventController::class, 'store']);
        Route::get('/events/{event}', [EventController::class, 'show']);
        Route::put('/events/{event}', [EventController::class, 'update']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);


        Route::post('/change-password', [AdminController::class, 'changePassword']);
        Route::get('/admin/activities', [AdminActivityController::class, 'index']);
        Route::get('/admin/activities/performance', [AdminActivityController::class, 'performance']);
        Route::put('/profile', [AdminController::class, 'updateProfile']);

    });


});






