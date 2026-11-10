<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { GraduationCap, ShieldCheck, User as UserIcon, X } from '@lucide/vue';
import EnrollmentController from '@/actions/App/Http/Controllers/Admin/EnrollmentController';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useInitials } from '@/composables/useInitials';

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        groups: { id: number; name: string }[];
        courses: {
            id: number;
            title: string;
            pivot: { id: number };
        }[];
    };
    availableCourses: { id: number; title: string }[];
}>();

const { getInitials } = useInitials();

function isAdmin() {
    return props.user.groups.some((group) => group.name === 'admin');
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
    <Head :title="`Admin – ${user.name}`" />

    <div class="flex flex-col gap-6 p-4">
        <AdminHeader
            :icon="UserIcon"
            :title="user.name"
            :description="user.email"
            :back-href="UserController.index()"
            back-label="Vissza a felhasználókhoz"
        />

        <Card class="gap-4 p-5">
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
                                v-if="isAdmin()"
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
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <GraduationCap class="size-4" />
                    Hozzárendelt kurzusok
                </CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
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
                    <p
                        v-if="user.courses.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Még nincs hozzárendelt kurzus.
                    </p>
                </div>

                <Form
                    v-if="availableCourses.length > 0"
                    v-bind="EnrollmentController.store.form(user.id)"
                    class="flex items-center gap-2"
                >
                    <Select
                        name="course_id"
                        :default-value="String(availableCourses[0]?.id)"
                    >
                        <SelectTrigger size="sm" class="w-64">
                            <SelectValue placeholder="Válassz kurzust" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="course in availableCourses"
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
            </CardContent>
        </Card>
    </div>
</template>
