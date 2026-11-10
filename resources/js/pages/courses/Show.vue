<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, FileDown } from '@lucide/vue';
import CourseController from '@/actions/App/Http/Controllers/CourseController';
import LessonController from '@/actions/App/Http/Controllers/LessonController';
import PageController from '@/actions/App/Http/Controllers/PageController';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    course: {
        title: string;
        slug: string;
        description: string | null;
        thumbnail: string | null;
    };
    continueTo: { lessonSlug: string; pageSlug: string | null } | null;
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
        <img
            v-if="course.thumbnail"
            :src="`/storage/${course.thumbnail}`"
            :alt="course.title"
            class="h-48 w-full rounded-lg object-cover"
        />

        <div>
            <h1 class="text-xl font-semibold tracking-tight">
                {{ course.title }}
            </h1>
            <p v-if="course.description" class="text-sm text-muted-foreground">
                {{ course.description }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Button v-if="continueTo" as-child class="w-fit">
                <Link
                    :href="
                        continueTo.pageSlug
                            ? PageController.show([
                                  props.course.slug,
                                  continueTo.lessonSlug,
                                  continueTo.pageSlug,
                              ])
                            : LessonController.show([
                                  props.course.slug,
                                  continueTo.lessonSlug,
                              ])
                    "
                >
                    Kurzus folytatása
                    <ArrowRight class="size-4" />
                </Link>
            </Button>

            <Button as-child variant="outline" class="w-fit">
                <a :href="CourseController.pdf(course.slug).url">
                    <FileDown class="size-4" />
                    Letöltés PDF-ben
                </a>
            </Button>
        </div>
    </div>
</template>
