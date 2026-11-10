<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { GraduationCap, ShieldCheck, Users, X } from '@lucide/vue';
import { computed } from 'vue';
import EnrollmentController from '@/actions/App/Http/Controllers/Admin/EnrollmentController';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useInitials } from '@/composables/useInitials';

const props = defineProps<{
    users: {
        id: number;
        name: string;
        email: string;
        groups: { id: number; name: string }[];
        courses: {
            id: number;
            title: string;
            pivot: { id: number };
        }[];
    }[];
    courses: { id: number; title: string }[];
}>();

const { getInitials } = useInitials();

function isAdmin(user: (typeof props.users)[number]) {
    return user.groups.some((group) => group.name === 'admin');
}

const adminCount = computed(
    () => props.users.filter((user) => isAdmin(user)).length,
);

const totalEnrollments = computed(() =>
    props.users.reduce((sum, user) => sum + user.courses.length, 0),
);

function availableCourses(user: (typeof props.users)[number]) {
    const enrolledIds = new Set(user.courses.map((course) => course.id));

    return props.courses.filter((course) => !enrolledIds.has(course.id));
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin – Felhasználók', href: UserController.index() },
        ],
    },
});
</script>

<template>
    <Head title="Admin – Felhasználók" />

    <div class="flex flex-col gap-6 p-4">
        <AdminHeader
            :icon="Users"
            title="Felhasználók"
            description="Kezeld a felhasználókat és a kurzus hozzárendeléseket."
        />

        <div class="grid gap-4 sm:grid-cols-3">
            <Card class="flex-row items-center gap-4 px-5 py-4">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <Users class="size-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold leading-none">
                        {{ users.length }}
                    </p>
                    <p class="text-xs text-muted-foreground">Felhasználó</p>
                </div>
            </Card>
            <Card class="flex-row items-center gap-4 px-5 py-4">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400"
                >
                    <ShieldCheck class="size-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold leading-none">
                        {{ adminCount }}
                    </p>
                    <p class="text-xs text-muted-foreground">Admin</p>
                </div>
            </Card>
            <Card class="flex-row items-center gap-4 px-5 py-4">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                >
                    <GraduationCap class="size-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold leading-none">
                        {{ totalEnrollments }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Kurzus hozzárendelés
                    </p>
                </div>
            </Card>
        </div>

        <div class="flex flex-col gap-4">
            <Card
                v-for="user in users"
                :key="user.id"
                class="gap-4 p-5"
            >
                <div class="flex items-center gap-3">
                    <Avatar class="size-10">
                        <AvatarFallback
                            class="bg-primary/10 font-semibold text-primary"
                        >
                            {{ getInitials(user.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 flex-1">
                        <p class="flex items-center gap-2 font-medium">
                            {{ user.name }}
                            <Badge
                                v-for="group in user.groups"
                                :key="group.id"
                                :class="
                                    group.name === 'admin'
                                        ? 'gap-1 border-transparent bg-amber-500/15 text-amber-700 dark:text-amber-400'
                                        : 'gap-1 border-transparent bg-muted text-muted-foreground'
                                "
                            >
                                <ShieldCheck
                                    v-if="group.name === 'admin'"
                                    class="size-3"
                                />
                                {{ group.name }}
                            </Badge>
                        </p>
                        <p class="truncate text-sm text-muted-foreground">
                            {{ user.email }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        v-for="course in user.courses"
                        :key="course.id"
                        variant="secondary"
                        class="gap-1.5 py-1.5 pr-1.5 pl-2.5"
                    >
                        {{ course.title }}
                        <Form
                            v-bind="
                                EnrollmentController.destroy.form(
                                    course.pivot.id,
                                )
                            "
                        >
                            <button
                                type="submit"
                                class="rounded-full p-0.5 text-muted-foreground hover:bg-background hover:text-destructive"
                            >
                                <X class="size-3" />
                            </button>
                        </Form>
                    </Badge>

                    <Form
                        v-if="availableCourses(user).length > 0"
                        v-bind="EnrollmentController.store.form(user.id)"
                        class="flex items-center gap-2"
                    >
                        <Select
                            name="course_id"
                            :default-value="
                                String(availableCourses(user)[0]?.id)
                            "
                        >
                            <SelectTrigger size="sm" class="w-48">
                                <SelectValue placeholder="Válassz kurzust" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="course in availableCourses(user)"
                                    :key="course.id"
                                    :value="String(course.id)"
                                >
                                    {{ course.title }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Button type="submit" size="sm" variant="outline"
                            >Hozzárendelés</Button
                        >
                    </Form>
                </div>
            </Card>
        </div>
    </div>
</template>
