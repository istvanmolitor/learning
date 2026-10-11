<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import { Button } from '@/components/ui/button';

defineProps<{
    courses: {
        id: number;
        title: string;
        slug: string;
        is_published: boolean;
        lessons_count: number;
    }[];
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
    <Head title="Admin – Kurzusok" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold tracking-tight">Kurzusok</h1>
            <Link :href="CourseController.create()">
                <Button>Új kurzus</Button>
            </Link>
        </div>

        <div
            class="overflow-x-auto rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Cím</th>
                        <th class="p-3">Leckék</th>
                        <th class="p-3">Publikus</th>
                        <th class="p-3" />
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="course in courses"
                        :key="course.id"
                        class="border-t border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <td class="p-3">{{ course.title }}</td>
                        <td class="p-3">{{ course.lessons_count }}</td>
                        <td class="p-3">
                            {{ course.is_published ? 'Igen' : 'Nem' }}
                        </td>
                        <td class="flex items-center gap-2 p-3">
                            <Link :href="CourseController.edit(course.id)">
                                <Button variant="outline" size="sm"
                                    >Szerkesztés</Button
                                >
                            </Link>
                            <Form
                                v-bind="
                                    CourseController.destroy.form(course.id)
                                "
                            >
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    size="sm"
                                    >Törlés</Button
                                >
                            </Form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
