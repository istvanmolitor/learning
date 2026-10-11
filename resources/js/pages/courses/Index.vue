<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CourseController from '@/actions/App/Http/Controllers/CourseController';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

defineProps<{
    courses: {
        id: number;
        title: string;
        slug: string;
        description: string | null;
        lessons_count: number;
        completed_lessons_count: number;
    }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Kurzusaim', href: CourseController.index() }],
    },
});
</script>

<template>
    <Head title="Kurzusaim" />

    <div class="flex flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold tracking-tight">Kurzusaim</h1>

        <p v-if="courses.length === 0" class="text-sm text-muted-foreground">
            Jelenleg nincs hozzárendelt kurzusod.
        </p>

        <div v-else class="grid gap-4 md:grid-cols-2">
            <Link
                v-for="course in courses"
                :key="course.id"
                :href="CourseController.show(course.slug)"
            >
                <Card class="h-full transition-colors hover:border-ring">
                    <CardHeader>
                        <CardTitle>{{ course.title }}</CardTitle>
                        <CardDescription v-if="course.description">
                            {{ course.description }}
                        </CardDescription>
                        <CardDescription>
                            {{ course.completed_lessons_count }} /
                            {{ course.lessons_count }} lecke elvégezve
                        </CardDescription>
                    </CardHeader>
                </Card>
            </Link>
        </div>
    </div>
</template>
