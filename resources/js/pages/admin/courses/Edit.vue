<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import LessonController from '@/actions/App/Http/Controllers/Admin/LessonController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    course: {
        id: number;
        title: string;
        slug: string;
        description: string | null;
        is_published: boolean;
        order: number;
        lessons: { id: number; title: string; order: number }[];
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
        :title="course ? `Kurzus szerkesztése – ${course.title}` : 'Új kurzus'"
    />

    <div class="flex flex-col gap-6 p-4">
        <h1 class="text-xl font-semibold tracking-tight">
            {{ course ? 'Kurzus szerkesztése' : 'Új kurzus' }}
        </h1>

        <Form
            v-bind="
                course
                    ? CourseController.update.form(course.id)
                    : CourseController.store.form()
            "
            class="flex max-w-xl flex-col gap-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="title">Cím</Label>
                <Input
                    id="title"
                    name="title"
                    :default-value="course?.title"
                    required
                />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="slug">Slug</Label>
                <Input
                    id="slug"
                    name="slug"
                    :default-value="course?.slug"
                    required
                />
                <InputError :message="errors.slug" />
            </div>

            <div class="grid gap-2">
                <Label for="description">Leírás</Label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >{{ course?.description }}</textarea>
                <InputError :message="errors.description" />
            </div>

            <div class="grid gap-2">
                <Label for="order">Sorrend</Label>
                <Input
                    id="order"
                    type="number"
                    name="order"
                    :default-value="course?.order ?? 0"
                />
                <InputError :message="errors.order" />
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_published" value="0" />
                <input
                    type="checkbox"
                    name="is_published"
                    value="1"
                    :checked="course?.is_published"
                    class="accent-primary"
                />
                Publikus
            </label>

            <Button type="submit" :disabled="processing" class="w-fit"
                >Mentés</Button
            >
        </Form>

        <div v-if="course" class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold tracking-tight">Leckék</h2>
                <Link :href="LessonController.create(course.id)">
                    <Button variant="outline" size="sm">Új lecke</Button>
                </Link>
            </div>

            <ul class="flex flex-col gap-2">
                <li
                    v-for="lesson in course.lessons"
                    :key="lesson.id"
                    class="flex items-center justify-between rounded-md border border-sidebar-border/70 p-3 text-sm dark:border-sidebar-border"
                >
                    <span>{{ lesson.title }}</span>
                    <Link :href="LessonController.edit(lesson.id)">
                        <Button variant="outline" size="sm">Szerkesztés</Button>
                    </Link>
                </li>
                <li
                    v-if="course.lessons.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Még nincs lecke.
                </li>
            </ul>
        </div>
    </div>
</template>
