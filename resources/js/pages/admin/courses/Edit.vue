<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { BookOpen, GraduationCap, Plus } from '@lucide/vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import LessonController from '@/actions/App/Http/Controllers/Admin/LessonController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <AdminHeader
            :icon="GraduationCap"
            :title="course ? 'Kurzus szerkesztése' : 'Új kurzus'"
            :description="
                course
                    ? course.title
                    : 'Töltsd ki az alapadatokat az új kurzushoz.'
            "
            :back-href="CourseController.index()"
            back-label="Kurzusok"
        />

        <Card>
            <CardHeader>
                <CardTitle>Alapadatok</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="
                        course
                            ? CourseController.update.form(course.id)
                            : CourseController.store.form()
                    "
                    class="flex flex-col gap-5"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
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
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Leírás</Label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            >{{ course?.description }}</textarea
                        >
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2 sm:w-40">
                        <Label for="order">Sorrend</Label>
                        <Input
                            id="order"
                            type="number"
                            name="order"
                            :default-value="course?.order ?? 0"
                        />
                        <InputError :message="errors.order" />
                    </div>

                    <label
                        for="is_published"
                        class="flex cursor-pointer items-start gap-3 rounded-lg border border-input p-4 transition-colors hover:border-primary/50"
                    >
                        <input type="hidden" name="is_published" value="0" />
                        <Checkbox
                            id="is_published"
                            name="is_published"
                            value="1"
                            :default-value="course?.is_published ?? false"
                            class="mt-0.5"
                        />
                        <span>
                            <span class="block text-sm font-medium"
                                >Publikus</span
                            >
                            <span class="block text-xs text-muted-foreground"
                                >A kurzus megjelenik a tanulók számára a
                                felületen.</span
                            >
                        </span>
                    </label>

                    <Button type="submit" :disabled="processing" class="w-fit"
                        >Mentés</Button
                    >
                </Form>
            </CardContent>
        </Card>

        <Card v-if="course">
            <CardHeader class="flex items-center justify-between">
                <CardTitle>Leckék</CardTitle>
                <Link :href="LessonController.create(course.id)">
                    <Button variant="outline" size="sm">
                        <Plus class="size-3.5" />
                        Új lecke
                    </Button>
                </Link>
            </CardHeader>
            <CardContent>
                <ul
                    v-if="course.lessons.length > 0"
                    class="flex flex-col gap-2"
                >
                    <li
                        v-for="(lesson, index) in course.lessons"
                        :key="lesson.id"
                        class="flex items-center gap-3 rounded-lg border border-border p-3 text-sm transition-colors hover:bg-muted/40"
                    >
                        <span
                            class="flex size-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary"
                        >
                            {{ index + 1 }}
                        </span>
                        <BookOpen class="size-4 text-muted-foreground" />
                        <span class="flex-1">{{ lesson.title }}</span>
                        <Link :href="LessonController.edit(lesson.id)">
                            <Button variant="outline" size="sm"
                                >Szerkesztés</Button
                            >
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    Még nincs lecke.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
