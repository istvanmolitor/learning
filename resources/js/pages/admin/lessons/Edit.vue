<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import LessonController from '@/actions/App/Http/Controllers/Admin/LessonController';
import PageController from '@/actions/App/Http/Controllers/Admin/PageController';
import QuestionController from '@/actions/App/Http/Controllers/Admin/QuestionController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    course: { id: number; title: string };
    lesson: {
        id: number;
        title: string;
        slug: string;
        order: number;
        pages: { id: number; title: string }[];
        questions: { id: number; text: string }[];
    } | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin – Kurzusok', href: CourseController.index() },
        ],
    },
});
</script>

<template>
    <Head
        :title="lesson ? `Lecke szerkesztése – ${lesson.title}` : 'Új lecke'"
    />

    <div class="flex flex-col gap-6 p-4">
        <div>
            <Link
                :href="CourseController.edit(course.id)"
                class="text-sm text-muted-foreground hover:underline"
            >
                &larr; {{ course.title }}
            </Link>
            <h1 class="text-xl font-semibold tracking-tight">
                {{ lesson ? 'Lecke szerkesztése' : 'Új lecke' }}
            </h1>
        </div>

        <Form
            v-bind="
                lesson
                    ? LessonController.update.form(lesson.id)
                    : LessonController.store.form(course.id)
            "
            class="flex max-w-xl flex-col gap-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="title">Cím</Label>
                <Input
                    id="title"
                    name="title"
                    :default-value="lesson?.title"
                    required
                />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="slug">Slug</Label>
                <Input
                    id="slug"
                    name="slug"
                    :default-value="lesson?.slug"
                    required
                />
                <InputError :message="errors.slug" />
            </div>

            <div class="grid gap-2">
                <Label for="order">Sorrend</Label>
                <Input
                    id="order"
                    type="number"
                    name="order"
                    :default-value="lesson?.order ?? 0"
                />
                <InputError :message="errors.order" />
            </div>

            <Button type="submit" :disabled="processing" class="w-fit"
                >Mentés</Button
            >
        </Form>

        <template v-if="lesson">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold tracking-tight">
                        Oldalak
                    </h2>
                    <Link :href="PageController.create(lesson.id)">
                        <Button variant="outline" size="sm">Új oldal</Button>
                    </Link>
                </div>

                <ul class="flex flex-col gap-2">
                    <li
                        v-for="page in lesson.pages"
                        :key="page.id"
                        class="flex items-center justify-between rounded-md border border-sidebar-border/70 p-3 text-sm dark:border-sidebar-border"
                    >
                        <span>{{ page.title }}</span>
                        <Link :href="PageController.edit(page.id)">
                            <Button variant="outline" size="sm"
                                >Szerkesztés</Button
                            >
                        </Link>
                    </li>
                    <li
                        v-if="lesson.pages.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Még nincs oldal.
                    </li>
                </ul>
            </div>

            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold tracking-tight">
                        Teszt kérdések
                    </h2>
                    <Link :href="QuestionController.create(lesson.id)">
                        <Button variant="outline" size="sm">Új kérdés</Button>
                    </Link>
                </div>

                <ul class="flex flex-col gap-2">
                    <li
                        v-for="question in lesson.questions"
                        :key="question.id"
                        class="flex items-center justify-between rounded-md border border-sidebar-border/70 p-3 text-sm dark:border-sidebar-border"
                    >
                        <span>{{ question.text }}</span>
                        <Link :href="QuestionController.edit(question.id)">
                            <Button variant="outline" size="sm"
                                >Szerkesztés</Button
                            >
                        </Link>
                    </li>
                    <li
                        v-if="lesson.questions.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Még nincs kérdés.
                    </li>
                </ul>
            </div>
        </template>
    </div>
</template>
