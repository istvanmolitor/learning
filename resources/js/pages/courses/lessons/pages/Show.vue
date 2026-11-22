<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import LessonController from '@/actions/App/Http/Controllers/LessonController';
import BlockRenderer from '@/components/blocks/BlockRenderer.vue';
import PagePager from '@/components/courses/PagePager.vue';
import type { ContentBlock } from '@/lib/blocks/types';

const props = defineProps<{
    course: { title: string; slug: string };
    lesson: { title: string; slug: string };
    page: {
        title: string;
        slug: string;
        is_completed: boolean;
        narration_audio_url: string | null;
    };
    pagination: {
        index: number | null;
        total: number;
        previous: { slug: string; title: string } | null;
        next: { slug: string; title: string } | null;
        dots: { slug: string; is_completed: boolean }[];
    };
    blocks: ContentBlock[];
    contentHtml: string;
}>();
</script>

<template>
    <Head :title="page.title" />

    <div class="flex flex-col gap-6 p-4">
        <div
            class="sticky top-0 z-10 -mx-4 flex items-center justify-between border-b bg-background/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/60"
        >
            <div>
                <Link
                    :href="LessonController.show([course.slug, lesson.slug])"
                    class="text-sm text-muted-foreground hover:underline"
                >
                    &larr; {{ lesson.title }}
                </Link>
                <p
                    v-if="pagination.index"
                    class="text-xs text-muted-foreground"
                >
                    {{ pagination.index }}. / {{ pagination.total }} oldal
                </p>
            </div>
        </div>

        <h1 class="text-2xl font-semibold tracking-tight">{{ page.title }}</h1>

        <audio
            v-if="page.narration_audio_url"
            controls
            class="w-full"
            :src="page.narration_audio_url"
        />

        <BlockRenderer v-if="blocks.length" :blocks="blocks" />
        <div
            v-else
            class="prose max-w-none prose-neutral dark:prose-invert"
            v-html="contentHtml"
        />

        <PagePager
            :course="course"
            :lesson="lesson"
            :page="page"
            :pagination="pagination"
        />
    </div>
</template>
