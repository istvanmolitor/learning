<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import EnrollmentController from '@/actions/App/Http/Controllers/Admin/EnrollmentController';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    users: {
        id: number;
        name: string;
        email: string;
        is_admin: boolean;
        courses: {
            id: number;
            title: string;
            pivot: { id: number };
        }[];
    }[];
    courses: { id: number; title: string }[];
}>();

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

    <div class="flex flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold tracking-tight">Felhasználók</h1>

        <div class="flex flex-col gap-4">
            <div
                v-for="user in users"
                :key="user.id"
                class="flex flex-col gap-3 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium">
                            {{ user.name }}
                            <Badge
                                v-if="user.is_admin"
                                variant="secondary"
                                class="ml-2"
                                >Admin</Badge
                            >
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ user.email }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div
                        v-for="course in user.courses"
                        :key="course.id"
                        class="flex items-center gap-1 rounded-full border border-sidebar-border/70 px-3 py-1 text-xs dark:border-sidebar-border"
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
                                class="text-muted-foreground hover:text-destructive"
                            >
                                &times;
                            </button>
                        </Form>
                    </div>

                    <Form
                        v-if="availableCourses(user).length > 0"
                        v-bind="EnrollmentController.store.form(user.id)"
                        class="flex items-center gap-2"
                    >
                        <select
                            name="course_id"
                            class="h-8 rounded-md border border-input bg-transparent px-2 text-xs shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option
                                v-for="course in availableCourses(user)"
                                :key="course.id"
                                :value="course.id"
                            >
                                {{ course.title }}
                            </option>
                        </select>
                        <Button type="submit" size="sm" variant="outline"
                            >Hozzárendelés</Button
                        >
                    </Form>
                </div>
            </div>
        </div>
    </div>
</template>
