<?php

use App\Http\Controllers\Admin\ContentBlockController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseEnrollmentController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\GroupUserController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified'])->group(function () {
    // Viewing the course list and creating a course only require manage-courses OR
    // create-courses; authorization is enforced via CoursePolicy inside the controller
    // so that owners who only hold create-courses can also reach their own courses.
    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('courses/import', [CourseController::class, 'importCreate'])->name('courses.import.create');
    Route::post('courses/import', [CourseController::class, 'import'])->name('courses.import');
    Route::get('courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::match(['put', 'patch'], 'courses/{course}', [CourseController::class, 'update'])->name('courses.update');

    // Assigning/removing users on a course: allowed for manage-users holders or the
    // course's own creator (enforced via CoursePolicy::manageEnrollments).
    Route::post('courses/{course}/enrollments', [CourseEnrollmentController::class, 'store'])->name('courses.enrollments.store');
    Route::delete('courses/{course}/enrollments/{enrollment}', [CourseEnrollmentController::class, 'destroy'])->name('courses.enrollments.destroy');

    Route::middleware('permission:manage-courses')->group(function () {
        Route::get('courses/export', [CourseController::class, 'exportIndex'])->name('courses.export.index');
        Route::get('courses/{course}/export', [CourseController::class, 'export'])->name('courses.export');
        Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
        Route::resource('courses.lessons', LessonController::class)->shallow()->except(['show', 'index']);
        Route::resource('lessons.pages', PageController::class)->shallow()->except(['show', 'index']);
        Route::resource('pages.content-blocks', ContentBlockController::class)->shallow()->only(['store', 'update', 'destroy']);
        Route::resource('lessons.questions', QuestionController::class)->shallow()->except(['show', 'index']);
    });

    Route::middleware('permission:manage-users')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('users/{user}/enrollments', [EnrollmentController::class, 'store'])->name('users.enrollments.store');
        Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
    });

    Route::middleware('permission:manage-groups')->group(function () {
        Route::resource('groups', GroupController::class)->except('show');
        Route::post('groups/{group}/users', [GroupUserController::class, 'store'])->name('groups.users.store');
        Route::delete('groups/{group}/users/{user}', [GroupUserController::class, 'destroy'])->name('groups.users.destroy');
    });
});
