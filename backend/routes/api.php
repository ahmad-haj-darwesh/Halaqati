<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ExaminerApiController;
use App\Http\Controllers\Api\SupervisorApiController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\TeacherDailyController;
use App\Http\Controllers\Api\TeacherStudentProfileController;
use App\Http\Controllers\Api\TeacherStudentsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::post('/login', [AuthController::class, 'login']);

/** عرض صور الطلاب عبر Laravel ليُطبَّق CORS (ضروري لـ Flutter Web و Image.network) */
Route::get('/student-photo', function (\Illuminate\Http\Request $request) {
    $path = $request->query('path');
    if (! is_string($path) || $path === '') {
        abort(404);
    }
    if (! str_starts_with($path, 'student-photos/') && ! str_starts_with($path, 'teacher-photos/')) {
        abort(404);
    }
    if (str_contains($path, '..') || str_contains($path, '\\')) {
        abort(404);
    }
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }
    $fullPath = Storage::disk('public')->path($path);

    return response()->file($fullPath, [
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('api.student-photo');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/session', [AuthController::class, 'session']);
    Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->whereNumber('id');

    Route::middleware('role:Teacher')->prefix('teacher')->group(function () {
        Route::get('/me', [TeacherController::class, 'me']);
        Route::put('/profile', [TeacherController::class, 'updateProfile']);
        Route::post('/profile', [TeacherController::class, 'updateProfile']);
        Route::get('/students', [TeacherStudentsController::class, 'index']);
        Route::get('/students/{student}', [TeacherStudentsController::class, 'show']);
        Route::get('/students/{student}/profile', [TeacherStudentProfileController::class, 'show']);
        Route::put('/students/{student}/profile', [TeacherStudentProfileController::class, 'update']);
        // multipart/FormData لا يُفسَّر في PHP لطلبات PUT — استخدم POST عند رفع صورة من التطبيق
        Route::post('/students/{student}/profile', [TeacherStudentProfileController::class, 'update']);
        Route::post('/students/{student}/profile/submit', [TeacherStudentProfileController::class, 'submit']);

        Route::get('/halaqah/today', [TeacherDailyController::class, 'today']);
        Route::post('/daily-records/upsert', [TeacherDailyController::class, 'upsert']);
        Route::get('/reports/monthly', [TeacherDailyController::class, 'monthly']);
    });

    Route::middleware('role:Examiner')->prefix('examiner')->group(function () {
        Route::get('/me', [ExaminerApiController::class, 'me']);
        Route::get('/stats', [ExaminerApiController::class, 'stats']);
        Route::get('/daily-summary', [ExaminerApiController::class, 'dailySummary']);
        Route::get('/students/{student}', [ExaminerApiController::class, 'studentDetail']);
        Route::post('/test-results', [ExaminerApiController::class, 'storeResult']);
        Route::get('/tests', [ExaminerApiController::class, 'tests']);
        Route::get('/tests/{test}/assignments', [ExaminerApiController::class, 'assignmentsForTest']);
        Route::get('/test-assignments', [ExaminerApiController::class, 'testAssignments']);
        Route::get('/test-results', [ExaminerApiController::class, 'testResults']);
    });

    Route::middleware('role:CenterSupervisor')->prefix('supervisor')->group(function () {
        Route::get('/me', [SupervisorApiController::class, 'me']);
        Route::get('/stats', [SupervisorApiController::class, 'stats']);
        Route::get('/centers', [SupervisorApiController::class, 'centers']);
        Route::get('/halaqahs', [SupervisorApiController::class, 'halaqahs']);
        Route::get('/teachers', [SupervisorApiController::class, 'teachers']);
        Route::get('/teachers/{teacher}', [SupervisorApiController::class, 'teacherDetail']);
        Route::get('/halaqahs/{halaqah}/daily', [SupervisorApiController::class, 'halaqahDaily']);
        Route::post('/visits', [SupervisorApiController::class, 'storeVisit']);
        Route::get('/visits', [SupervisorApiController::class, 'myVisits']);
        Route::get('/attendance-stats', [SupervisorApiController::class, 'attendanceStats']);
    });
});
