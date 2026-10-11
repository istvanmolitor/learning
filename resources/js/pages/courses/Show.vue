<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, Circle } from '@lucide/vue';
import CourseController from '@/actions/App/Http/Controllers/CourseController';
import LessonController from '@/actions/App/Http/Controllers/LessonController';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps<{
    course: { title: string; slug: string; description: string | null };
    lessons: {
        id: number;
        title: string;
        slug: string;
        is_completed: boolean;
    }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Kurzusaim', href: CourseController.index() }],
    },
});
</script>

<template>
    <Head :title="course.title" />

    <div class="flex flex-col gap-4 p-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">
                {{ course.title }}
            </h1>
            <p v-if="course.description" class="text-sm text-muted-foreground">
                {{ course.description }}
            </p>
        </div>

        <div class="grid gap-3">
            <Link
                v-for="lesson in lessons"
                :key="lesson.id"
                :href="LessonController.show([props.course.slug, lesson.slug])"
            >
                <Card class="transition-colors hover:border-ring">
                    <CardHeader
                        class="flex-row items-center justify-between space-y-0"
                    >
                        <CardTitle>{{ lesson.title }}</CardTitle>
                        <CheckCircle2
                            v-if="lesson.is_completed"
                            class="size-5 text-green-600"
                        />
                        <Circle v-else class="size-5 text-muted-foreground" />
                    </CardHeader>
                </Card>
            </Link>
        </div>
    </div>
</template>
