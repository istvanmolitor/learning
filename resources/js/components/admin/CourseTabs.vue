<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import CourseEnrollmentController from '@/actions/App/Http/Controllers/Admin/CourseEnrollmentController';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn } from '@/lib/utils';
import type { InertiaLinkProps } from '@inertiajs/vue3';

const props = defineProps<{
    courseId: number;
    canManageEnrollments?: boolean;
}>();

const { isCurrentUrl } = useCurrentUrl();

const tabs = computed(() => {
    const items: {
        title: string;
        href: NonNullable<InertiaLinkProps['href']>;
    }[] = [
        { title: 'Alapadatok', href: CourseController.edit(props.courseId) },
        { title: 'Szerkesztő', href: CourseController.lessons(props.courseId) },
    ];

    if (props.canManageEnrollments) {
        items.push({
            title: 'Hozzárendelés',
            href: CourseEnrollmentController.index(props.courseId),
        });
    }

    return items;
});
</script>

<template>
    <nav
        class="flex gap-1 border-b border-border"
        aria-label="Kurzus szerkesztése"
    >
        <Button
            v-for="tab in tabs"
            :key="tab.title"
            variant="ghost"
            :class="
                cn(
                    'rounded-none border-b-2 border-transparent',
                    isCurrentUrl(tab.href) && 'border-primary text-primary',
                )
            "
            as-child
        >
            <Link :href="tab.href">{{ tab.title }}</Link>
        </Button>
    </nav>
</template>
