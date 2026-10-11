<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CheckCircle2, Circle } from '@lucide/vue';
import CourseController from '@/actions/App/Http/Controllers/CourseController';
import LessonController from '@/actions/App/Http/Controllers/LessonController';
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

defineProps<{
    course: { title: string; slug: string };
    lessons: {
        id: number;
        title: string;
        slug: string;
        is_completed: boolean;
    }[];
    activeLessonSlug?: string | null;
}>();
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="CourseController.index()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>{{ course.title }}</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem
                        v-for="lesson in lessons"
                        :key="lesson.id"
                    >
                        <SidebarMenuButton
                            as-child
                            :is-active="lesson.slug === activeLessonSlug"
                            :tooltip="lesson.title"
                        >
                            <Link
                                :href="
                                    LessonController.show([
                                        course.slug,
                                        lesson.slug,
                                    ])
                                "
                            >
                                <CheckCircle2
                                    v-if="lesson.is_completed"
                                    class="text-green-600"
                                />
                                <Circle v-else />
                                <span>{{ lesson.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
