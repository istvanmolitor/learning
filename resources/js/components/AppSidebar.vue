<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { GraduationCap, Lock, Shield, Users } from '@lucide/vue';
import { computed } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import GroupController from '@/actions/App/Http/Controllers/Admin/GroupController';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as coursesIndex } from '@/routes/courses';
import type { NavItem } from '@/types';

const page = usePage();
const permissions = computed(() => page.props.auth.permissions);

const mainNavItems: NavItem[] = [
    {
        title: 'Kurzusaim',
        href: coursesIndex(),
        icon: GraduationCap,
    },
];

const adminNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (permissions.value.includes('manage-courses')) {
        items.push({
            title: 'Kurzusok',
            href: CourseController.index(),
            icon: Shield,
        });
    }

    if (permissions.value.includes('manage-users')) {
        items.push({
            title: 'Felhasználók',
            href: UserController.index(),
            icon: Users,
        });
    }

    if (permissions.value.includes('manage-groups')) {
        items.push({
            title: 'Csoportok',
            href: GroupController.index(),
            icon: Lock,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="coursesIndex()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain
                v-if="adminNavItems.length > 0"
                :items="adminNavItems"
                label="Admin"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
