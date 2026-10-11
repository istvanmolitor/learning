<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, Circle } from '@lucide/vue';
import CourseController from '@/actions/App/Http/Controllers/CourseController';
import LessonController from '@/actions/App/Http/Controllers/LessonController';
import PageController from '@/actions/App/Http/Controllers/PageController';
import QuestionResponseController from '@/actions/App/Http/Controllers/QuestionResponseController';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    course: { title: string; slug: string };
    lesson: { title: string; slug: string; is_completed: boolean };
    pages: { id: number; title: string; slug: string; is_completed: boolean }[];
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
    <Head :title="lesson.title" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <div>
                <Link
                    :href="CourseController.show(course.slug)"
                    class="text-sm text-muted-foreground hover:underline"
                >
                    &larr; {{ course.title }}
                </Link>
                <h1 class="text-xl font-semibold tracking-tight">
                    {{ lesson.title }}
                </h1>
            </div>

            <Form
                v-bind="
                    LessonController.complete.form([course.slug, lesson.slug])
                "
            >
                <Button v-if="!lesson.is_completed" type="submit"
                    >Lecke elvégzése</Button
                >
                <span
                    v-else
                    class="flex items-center gap-1 text-sm text-green-600"
                >
                    <CheckCircle2 class="size-4" /> Lecke elvégezve
                </span>
            </Form>
        </div>

        <div class="grid gap-3">
            <Link
                v-for="page in pages"
                :key="page.id"
                :href="
                    PageController.show([course.slug, lesson.slug, page.slug])
                "
            >
                <Card class="transition-colors hover:border-ring">
                    <CardHeader
                        class="flex-row items-center justify-between space-y-0"
                    >
                        <CardTitle>{{ page.title }}</CardTitle>
                        <CheckCircle2
                            v-if="page.is_completed"
                            class="size-5 text-green-600"
                        />
                        <Circle v-else class="size-5 text-muted-foreground" />
                    </CardHeader>
                </Card>
            </Link>
        </div>

        <div v-if="lesson.is_completed" class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold tracking-tight">Teszt kérdések</h2>

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
