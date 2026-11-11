<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { GraduationCap, X } from '@lucide/vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import CourseEnrollmentController from '@/actions/App/Http/Controllers/Admin/CourseEnrollmentController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import CourseTabs from '@/components/admin/CourseTabs.vue';
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

const props = defineProps<{
    course: {
        id: number;
        title: string;
        enrolledUsers: {
            id: number;
            name: string;
            email: string;
            pivot: { id: number };
        }[];
    };
    availableUsers: { id: number; name: string; email: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin – Kurzusok', href: CourseController.index() },
        ],
    },
});
</script>

<template>
    <Head :title="`Hozzárendelés – ${course.title}`" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <AdminHeader
            :icon="GraduationCap"
            title="Hozzárendelés"
            :description="course.title"
            :back-href="CourseController.index()"
            back-label="Kurzusok"
        />

        <CourseTabs :course-id="course.id" :can-manage-enrollments="true" />

        <Card>
            <CardHeader>
                <CardTitle>Hozzárendelt felhasználók</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        v-for="user in course.enrolledUsers"
                        :key="user.id"
                        variant="secondary"
                        class="gap-1.5 py-1.5 pr-1.5 pl-2.5"
                    >
                        {{ user.name }}
                        <Form
                            v-bind="
                                CourseEnrollmentController.destroy.form([
                                    course.id,
                                    user.pivot.id,
                                ])
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
                        v-if="course.enrolledUsers.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Még nincs hozzárendelt felhasználó.
                    </p>
                </div>

                <Form
                    v-if="availableUsers.length > 0"
                    v-bind="CourseEnrollmentController.store.form(course.id)"
                    class="flex items-center gap-2"
                >
                    <Select
                        name="user_id"
                        :default-value="String(availableUsers[0]?.id)"
                    >
                        <SelectTrigger size="sm" class="w-64">
                            <SelectValue placeholder="Válassz felhasználót" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="user in availableUsers"
                                :key="user.id"
                                :value="String(user.id)"
                            >
                                {{ user.name }} ({{ user.email }})
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
