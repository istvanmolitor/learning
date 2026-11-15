<script setup lang="ts">
import { computed } from 'vue';
import type { VideoData } from '@/lib/blocks/types';
import { mediaUrl } from '@/lib/blocks/media';

const props = defineProps<{ data: VideoData }>();

const embedUrl = computed(() => {
    if (props.data.source === 'youtube') {
        return `https://www.youtube.com/embed/${props.data.video_id}`;
    }
    if (props.data.source === 'vimeo') {
        return `https://player.vimeo.com/video/${props.data.video_id}`;
    }
    return null;
});
</script>

<template>
    <div class="my-4 overflow-hidden rounded-lg border bg-black">
        <div v-if="embedUrl" class="aspect-video">
            <iframe
                :src="embedUrl"
                :title="data.title ?? 'Videó'"
                class="h-full w-full"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
            />
        </div>
        <video
            v-else
            controls
            class="aspect-video w-full"
            :poster="data.poster ? mediaUrl(data.poster) : undefined"
            :src="mediaUrl(data.path)"
        />
    </div>
    <p v-if="data.title" class="mt-1 text-sm text-muted-foreground">
        {{ data.title }}
    </p>
</template>
