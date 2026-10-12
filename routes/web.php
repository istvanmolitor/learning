<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\QuestionResponseController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');

    Route::scopeBindings()->group(function () {
        Route::get('courses/{course:slug}/lessons/{lesson:slug}', [LessonController::class, 'show'])->name('lessons.show');
        Route::post('courses/{course:slug}/lessons/{lesson:slug}/complete', [LessonController::class, 'complete'])->name('lessons.complete');
        Route::get('courses/{course:slug}/lessons/{lesson:slug}/questions', [LessonController::class, 'questions'])->name('lessons.questions');

        Route::get('courses/{course:slug}/lessons/{lesson:slug}/pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');
        Route::post('courses/{course:slug}/lessons/{lesson:slug}/pages/{page:slug}/complete', [PageController::class, 'complete'])->name('pages.complete');

        Route::post('courses/{course:slug}/lessons/{lesson:slug}/questions/{question}/answer', [QuestionResponseController::class, 'store'])->name('questions.answer');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
