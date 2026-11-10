<?php

use App\Http\Controllers\Admin\ContentBlockController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\GroupUserController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified'])->group(function () {
    Route::middleware('permission:manage-courses')->group(function () {
        Route::post('courses/import', [CourseController::class, 'import'])->name('courses.import');
        Route::get('courses/{course}/export', [CourseController::class, 'export'])->name('courses.export');
        Route::resource('courses', CourseController::class)->except('show');
        Route::resource('courses.lessons', LessonController::class)->shallow()->except(['show', 'index']);
        Route::resource('lessons.pages', PageController::class)->shallow()->except(['show', 'index']);
        Route::resource('pages.content-blocks', ContentBlockController::class)->shallow()->only(['store', 'update', 'destroy']);
        Route::resource('lessons.questions', QuestionController::class)->shallow()->except(['show', 'index']);
    });

    Route::middleware('permission:manage-users')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users/{user}/enrollments', [EnrollmentController::class, 'store'])->name('users.enrollments.store');
        Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
    });

    Route::middleware('permission:manage-groups')->group(function () {
        Route::resource('groups', GroupController::class)->except('show');
        Route::post('groups/{group}/users', [GroupUserController::class, 'store'])->name('groups.users.store');
        Route::delete('groups/{group}/users/{user}', [GroupUserController::class, 'destroy'])->name('groups.users.destroy');
    });
});
