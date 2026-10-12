<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import CourseController from '@/actions/App/Http/Controllers/CourseController';
import LessonController from '@/actions/App/Http/Controllers/LessonController';
import QuestionResponseController from '@/actions/App/Http/Controllers/QuestionResponseController';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
    course: { title: string; slug: string };
    lesson: { title: string; slug: string; is_completed: boolean };
    questions: {
        id: number;
        text: string;
        options: { id: number; text: string }[];
        selected_answer_option_id: number | null;
        is_correct: boolean | null;
    }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Kurzusaim', href: CourseController.index() }],
    },
});
</script>

<template>
    <Head :title="`${lesson.title} – Teszt kérdések`" />

    <div class="flex flex-col gap-6 p-4">
        <div>
            <Link
                :href="LessonController.show([course.slug, lesson.slug])"
                class="text-sm text-muted-foreground hover:underline"
            >
                &larr; {{ lesson.title }}
            </Link>
            <h1 class="text-xl font-semibold tracking-tight">
                Teszt kérdések
            </h1>
        </div>

        <div
            v-if="!lesson.is_completed"
            class="flex flex-col items-start gap-3 rounded-lg border p-4"
        >
            <p class="text-sm text-muted-foreground">
                A teszt kérdések eléréséhez előbb fejezd be a leckét.
            </p>
            <Button as-child variant="outline">
                <Link :href="LessonController.show([course.slug, lesson.slug])">
                    Vissza a leckéhez
                </Link>
            </Button>
        </div>

        <div v-else class="flex flex-col gap-4">
            <Card v-for="question in questions" :key="question.id">
                <CardHeader>
                    <CardTitle class="text-base">{{ question.text }}</CardTitle>
                </CardHeader>
                <Form
                    v-bind="
                        QuestionResponseController.store.form([
                            course.slug,
                            lesson.slug,
                            question.id,
                        ])
                    "
                    class="flex flex-col gap-3 px-6 pb-6"
                    v-slot="{ processing }"
                >
                    <label
                        v-for="option in question.options"
                        :key="option.id"
                        class="flex items-center gap-2 text-sm"
                    >
                        <input
                            type="radio"
                            name="answer_option_id"
                            :value="option.id"
                            :checked="
                                option.id === question.selected_answer_option_id
                            "
                            class="accent-primary"
                        />
                        {{ option.text }}
                    </label>

                    <div class="flex items-center gap-3">
                        <Button type="submit" :disabled="processing" size="sm"
                            >Válasz beküldése</Button
                        >
                        <span
                            v-if="question.is_correct === true"
                            class="text-sm text-green-600"
                            >Helyes válasz!</span
                        >
                        <span
                            v-else-if="question.is_correct === false"
                            class="text-sm text-red-600"
                            >Helytelen válasz.</span
                        >
                    </div>
                </Form>
            </Card>
        </div>
    </div>
</template>
