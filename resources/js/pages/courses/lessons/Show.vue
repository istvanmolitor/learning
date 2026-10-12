<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle2, Circle } from '@lucide/vue';
import { computed } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/CourseController';
import LessonController from '@/actions/App/Http/Controllers/LessonController';
import PageController from '@/actions/App/Http/Controllers/PageController';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';

const props = defineProps<{
    course: { title: string; slug: string };
    lesson: { title: string; slug: string; is_completed: boolean };
    pages: { id: number; title: string; slug: string; is_completed: boolean }[];
}>();

const completedPagesCount = computed(
    () => props.pages.filter((page) => page.is_completed).length,
);

const progress = computed(() =>
    props.pages.length
        ? Math.round((completedPagesCount.value / props.pages.length) * 100)
        : 0,
);

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

        <div v-if="pages.length" class="flex flex-col gap-2">
            <div
                class="flex items-center justify-between text-sm text-muted-foreground"
            >
                <span>{{ completedPagesCount }} / {{ pages.length }} oldal kész</span>
                <span>{{ progress }}%</span>
            </div>
            <Progress :model-value="progress" />
        </div>

        <div class="flex flex-col divide-y rounded-lg border">
            <Link
                v-for="page in pages"
                :key="page.id"
                :href="
                    PageController.show([course.slug, lesson.slug, page.slug])
                "
                class="flex items-center justify-between gap-3 px-4 py-3 text-sm transition-colors hover:bg-muted/50"
            >
                <span>{{ page.title }}</span>
                <CheckCircle2
                    v-if="page.is_completed"
                    class="size-4 shrink-0 text-green-600"
                />
                <Circle v-else class="size-4 shrink-0 text-muted-foreground" />
            </Link>
        </div>

        <Button as-child variant="outline" class="w-fit">
            <Link :href="LessonController.questions([course.slug, lesson.slug])">
                Teszt kérdések
                <ArrowRight class="size-4" />
            </Link>
        </Button>
    </div>
</template>
