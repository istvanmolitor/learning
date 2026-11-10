<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { FileText, HelpCircle, Layers, Plus } from '@lucide/vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import LessonController from '@/actions/App/Http/Controllers/Admin/LessonController';
import PageController from '@/actions/App/Http/Controllers/Admin/PageController';
import QuestionController from '@/actions/App/Http/Controllers/Admin/QuestionController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <AdminHeader
            :icon="Layers"
            :title="lesson ? 'Lecke szerkesztése' : 'Új lecke'"
            :description="lesson ? lesson.title : 'Új lecke a kurzushoz.'"
            :back-href="CourseController.edit(course.id)"
            :back-label="course.title"
        />

        <Card>
            <CardHeader>
                <CardTitle>Alapadatok</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="
                        lesson
                            ? LessonController.update.form(lesson.id)
                            : LessonController.store.form(course.id)
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
                    </div>

                    <div class="grid gap-2 sm:w-40">
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
            </CardContent>
        </Card>

        <template v-if="lesson">
            <Card>
                <CardHeader class="flex items-center justify-between">
                    <CardTitle>Oldalak</CardTitle>
                    <Link :href="PageController.create(lesson.id)">
                        <Button variant="outline" size="sm">
                            <Plus class="size-3.5" />
                            Új oldal
                        </Button>
                    </Link>
                </CardHeader>
                <CardContent>
                    <ul
                        v-if="lesson.pages.length > 0"
                        class="flex flex-col gap-2"
                    >
                        <li
                            v-for="(page, index) in lesson.pages"
                            :key="page.id"
                            class="flex items-center gap-3 rounded-lg border border-border p-3 text-sm transition-colors hover:bg-muted/40"
                        >
                            <span
                                class="flex size-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary"
                            >
                                {{ index + 1 }}
                            </span>
                            <FileText class="size-4 text-muted-foreground" />
                            <span class="flex-1">{{ page.title }}</span>
                            <Link :href="PageController.edit(page.id)">
                                <Button variant="outline" size="sm"
                                    >Szerkesztés</Button
                                >
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        Még nincs oldal.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex items-center justify-between">
                    <CardTitle>Teszt kérdések</CardTitle>
                    <Link :href="QuestionController.create(lesson.id)">
                        <Button variant="outline" size="sm">
                            <Plus class="size-3.5" />
                            Új kérdés
                        </Button>
                    </Link>
                </CardHeader>
                <CardContent>
                    <ul
                        v-if="lesson.questions.length > 0"
                        class="flex flex-col gap-2"
                    >
                        <li
                            v-for="question in lesson.questions"
                            :key="question.id"
                            class="flex items-center gap-3 rounded-lg border border-border p-3 text-sm transition-colors hover:bg-muted/40"
                        >
                            <HelpCircle
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                            <span class="flex-1">{{ question.text }}</span>
                            <Link
                                :href="QuestionController.edit(question.id)"
                            >
                                <Button variant="outline" size="sm"
                                    >Szerkesztés</Button
                                >
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        Még nincs kérdés.
                    </p>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
