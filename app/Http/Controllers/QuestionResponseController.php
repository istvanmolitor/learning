<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuestionResponseController extends Controller
{
    public function store(Request $request, Course $course, Lesson $lesson, Question $question): RedirectResponse
    {
        Gate::authorize('view', $course);

        $user = $request->user();

        abort_unless($lesson->isCompletedBy($user), 403);

        $validated = $request->validate([
            'answer_option_id' => ['required', 'integer', 'exists:answer_options,id'],
        ]);

        abort_unless(
            $question->answerOptions()->whereKey($validated['answer_option_id'])->exists(),
            422,
        );

        QuestionResponse::query()->updateOrCreate(
            ['user_id' => $user->id, 'question_id' => $question->id],
            ['answer_option_id' => $validated['answer_option_id'], 'answered_at' => now()],
        );

        return back();
    }
}
