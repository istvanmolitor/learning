<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuestionRequest;
use App\Models\Lesson;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function create(Lesson $lesson): Response
    {
        return Inertia::render('admin/questions/Edit', [
            'lesson' => $lesson,
            'question' => null,
            'responses' => [],
        ]);
    }

    public function store(QuestionRequest $request, Lesson $lesson): RedirectResponse
    {
        $question = $lesson->questions()->create([
            'text' => $request->validated('text'),
            'order' => $request->validated('order') ?? 0,
        ]);

        $this->syncOptions($question, $request->validated('options'), (int) $request->validated('correct_option'));

        return to_route('admin.lessons.edit', $lesson);
    }

    public function edit(Question $question): Response
    {
        $question->load(['answerOptions', 'responses.user', 'responses.answerOption']);

        return Inertia::render('admin/questions/Edit', [
            'lesson' => $question->lesson,
            'question' => $question,
            'responses' => $question->responses,
        ]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $question->update([
            'text' => $request->validated('text'),
            'order' => $request->validated('order') ?? $question->order,
        ]);

        $this->syncOptions($question, $request->validated('options'), (int) $request->validated('correct_option'));

        return to_route('admin.lessons.edit', $question->lesson);
    }

    public function destroy(Question $question): RedirectResponse
    {
        $lesson = $question->lesson;
        $question->delete();

        return to_route('admin.lessons.edit', $lesson);
    }

    private function syncOptions(Question $question, array $options, int $correctOption): void
    {
        $keptIds = [];

        foreach ($options as $order => $option) {
            $attributes = [
                'text' => $option['text'],
                'is_correct' => $order === $correctOption,
                'order' => $order,
            ];

            if (! empty($option['id'])) {
                $question->answerOptions()->whereKey($option['id'])->update($attributes);
                $keptIds[] = $option['id'];
            } else {
                $keptIds[] = $question->answerOptions()->create($attributes)->id;
            }
        }

        $question->answerOptions()->whereNotIn('id', $keptIds)->delete();
    }
}
