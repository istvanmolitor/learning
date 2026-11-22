<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/CourseController';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import CourseSidebar from '@/components/CourseSidebar.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type SidebarLesson = {
    id: number;
    title: string;
    slug: string;
    is_completed: boolean;
};

const page = usePage();
const course = computed(
    () => page.props.course as { title: string; slug: string },
);
const lessons = computed(() => (page.props.lessons ?? []) as SidebarLesson[]);
const activeLessonSlug = computed(
    () => (page.props.lesson as { slug: string } | undefined)?.slug ?? null,
);
const courseBreadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: course.value.title,
        href: CourseController.show(course.value.slug),
    },
]);
</script>

<template>
    <AppShell variant="sidebar" style="--sidebar-width: 22rem">
        <CourseSidebar
            :course="course"
            :lessons="lessons"
            :active-lesson-slug="activeLessonSlug"
        />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="courseBreadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
