<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { CheckCircle2 } from '@lucide/vue';
import CourseController from '@/actions/App/Http/Controllers/CourseController';
import LessonController from '@/actions/App/Http/Controllers/LessonController';
import PageController from '@/actions/App/Http/Controllers/PageController';
import ContentBlock from '@/components/content-blocks/ContentBlock.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    course: { title: string; slug: string };
    lesson: { title: string; slug: string };
    page: { title: string; slug: string; is_completed: boolean };
    contentBlocks: {
        id: number;
        type: string;
        data: Record<string, unknown>;
    }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Kurzusaim', href: CourseController.index() }],
    },
});
</script>

<template>
    <Head :title="page.title" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Link
                :href="LessonController.show([course.slug, lesson.slug])"
                class="text-sm text-muted-foreground hover:underline"
            >
                &larr; {{ lesson.title }}
            </Link>

            <Form
                v-bind="
                    PageController.complete.form([
                        course.slug,
                        lesson.slug,
                        page.slug,
                    ])
                "
            >
                <Button v-if="!page.is_completed" type="submit"
                    >Oldal elvégzése</Button
                >
                <span
                    v-else
                    class="flex items-center gap-1 text-sm text-green-600"
                >
                    <CheckCircle2 class="size-4" /> Oldal elvégezve
                </span>
            </Form>
        </div>

        <div class="flex flex-col gap-6">
            <ContentBlock
                v-for="block in contentBlocks"
                :key="block.id"
                :block="block"
            />
        </div>
    </div>
</template>
