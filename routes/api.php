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


Route::post('/members', [MemberController::class, 'publicStore']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/public-testimonials', [TestimonialController::class, 'index']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

//event public routes
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);




Route::middleware(['auth:sanctum', 'last_seen'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/heartbeat', function () {

        $user = auth()->user();
        $user->forceFill(['last_seen' => now()])->save();

        return response()->json(['success' => true]);
    });


    Route::middleware('role:superadmin')->group(function () {
        Route::apiResource('users', AdminController::class);
        Route::get('/superadmin/search-admins', [AdminController::class, 'searchAdmin']);
        Route::get('/admin-status', [AdminController::class, 'activeAdmins']);
        Route::get('/security-logs', [SecurityLogController::class, 'index']);
        Route::post('/security-logs/bulk-delete', [SecurityLogController::class, 'bulkDelete']);
        Route::post('/admin-activities/bulk-delete', [AdminActivityController::class, 'activityDelete']);

    });

    Route::middleware('role:superadmin,admin')->group(function () {
        Route::get('/dashboard-stats', [SuperAdminDashboardController::class, 'getStats']);
        Route::get('/members/search', [MemberController::class, 'searchMember']);
        Route::apiResource('members', MemberController::class)->except(['store']);
        Route::post('/members/admin', [MemberController::class, 'adminStore']);
        Route::apiResource('testimonials', TestimonialController::class);
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{event}', [EventController::class, 'update']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);
        Route::post('/change-password', [AdminController::class, 'changePassword']);
        Route::get('/recent-public-members', [MemberController::class, 'recentPublicMembers']);
        Route::get('/admin/activities', [AdminActivityController::class, 'index']);
        Route::get('/admin/activities/performance', [AdminActivityController::class, 'performance']);
        Route::put('/profile', [AdminController::class, 'updateProfile']);



    });


});






