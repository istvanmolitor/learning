<script setup lang="ts">
defineProps<{
    data: { video_id: string; title: string | null };
}>();
</script>

<template>
    <div class="aspect-video w-full overflow-hidden rounded-lg">
        <iframe
            class="h-full w-full"
            :src="`https://www.youtube.com/embed/${data.video_id}`"
            :title="data.title ?? 'YouTube video'"
            frameborder="0"
            allowfullscreen
        />
    </div>
</template>
