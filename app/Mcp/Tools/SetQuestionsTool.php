<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\CourseTool;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Replace all of a lesson\'s quiz questions with the given list. Shown to learners after they finish the lesson\'s pages.')]
class SetQuestionsTool extends CourseTool
{
    protected function run(Request $request, User $user): Response
    {
        $course = $this->resolveCourse((string) $request->get('course_slug'));
        $lesson = $this->resolveLesson($course, (string) $request->get('lesson_slug'));

        /** @var list<array{text: string, answer_options?: list<array{text: string, is_correct?: bool}>}> $questions */
        $questions = $request->array('questions');

        $lesson->questions()->delete();

        foreach ($questions as $order => $questionData) {
            $question = $lesson->questions()->create([
                'text' => $questionData['text'],
                'order' => $order,
            ]);

            foreach ($questionData['answer_options'] ?? [] as $optionOrder => $optionData) {
                $question->answerOptions()->create([
                    'text' => $optionData['text'],
                    'is_correct' => $optionData['is_correct'] ?? false,
                    'order' => $optionOrder,
                ]);
            }
        }

        $this->logWrite($user, 'set_questions', [
            'course_slug' => $course->slug,
            'lesson_slug' => $lesson->slug,
            'question_count' => count($questions),
        ]);

        return Response::json(['status' => 'updated', 'question_count' => count($questions)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'course_slug' => $schema->string()->required(),
            'lesson_slug' => $schema->string()->required(),
            'questions' => $schema->array()
                ->description('Each item: {text, answer_options: [{text, is_correct}]}.')
                ->items($schema->object())
                ->required(),
        ];
    }
}
