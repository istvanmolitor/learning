<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CourseController from '@/actions/App/Http/Controllers/CourseController';
import { Badge } from '@/components/ui/badge';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';

defineProps<{
    courses: {
        id: number;
        title: string;
        slug: string;
        description: string | null;
        thumbnail: string | null;
        lessons_count: number;
        completed_lessons_count: number;
    }[];
}>();

function progressPercent(completed: number, total: number) {
    return total > 0 ? Math.round((completed / total) * 100) : 0;
}

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
                <Card
                    class="relative h-full flex-row items-center gap-4 p-4 transition-colors hover:border-ring"
                >
                    <Badge
                        class="absolute top-3 right-3 border-transparent bg-primary/10 text-primary"
                    >
                        {{
                            progressPercent(
                                course.completed_lessons_count,
                                course.lessons_count,
                            )
                        }}%
                    </Badge>

                    <img
                        v-if="course.thumbnail"
                        :src="`/storage/${course.thumbnail}`"
                        :alt="course.title"
                        class="size-20 shrink-0 rounded-lg object-cover"
                    />
                    <div
                        v-else
                        class="flex size-20 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-2xl font-semibold text-primary"
                    >
                        {{ course.title.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex min-w-0 flex-col gap-1 pr-12">
                        <CardTitle>{{ course.title }}</CardTitle>
                        <CardDescription
                            v-if="course.description"
                            class="line-clamp-2"
                        >
                            {{ course.description }}
                        </CardDescription>
                        <CardDescription>
                            {{ course.completed_lessons_count }} /
                            {{ course.lessons_count }} lecke elvégezve
                        </CardDescription>
                    </div>
                </Card>
            </Link>
        </div>
    </div>
</template>
