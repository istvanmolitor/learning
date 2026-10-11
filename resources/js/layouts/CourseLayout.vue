<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
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

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const course = computed(
    () => page.props.course as { title: string; slug: string },
);
const lessons = computed(() => (page.props.lessons ?? []) as SidebarLesson[]);
const activeLessonSlug = computed(
    () => (page.props.lesson as { slug: string } | undefined)?.slug ?? null,
);
</script>

<template>
    <AppShell variant="sidebar">
        <CourseSidebar
            :course="course"
            :lessons="lessons"
            :active-lesson-slug="activeLessonSlug"
        />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
