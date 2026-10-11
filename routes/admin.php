<?php

use App\Http\Controllers\Admin\ContentBlockController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::resource('courses', CourseController::class)->except('show');
    Route::resource('courses.lessons', LessonController::class)->shallow()->except(['show', 'index']);
    Route::resource('lessons.pages', PageController::class)->shallow()->except(['show', 'index']);
    Route::resource('pages.content-blocks', ContentBlockController::class)->shallow()->only(['store', 'update', 'destroy']);
    Route::resource('lessons.questions', QuestionController::class)->shallow()->except(['show', 'index']);

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users/{user}/enrollments', [EnrollmentController::class, 'store'])->name('users.enrollments.store');
    Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
});
