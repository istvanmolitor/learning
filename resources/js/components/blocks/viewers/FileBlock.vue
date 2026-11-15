<script setup lang="ts">
import { Paperclip } from '@lucide/vue';
import type { FileData } from '@/lib/blocks/types';
import { mediaUrl } from '@/lib/blocks/media';

defineProps<{ data: FileData }>();
</script>

<template>
    <a
        :href="mediaUrl(data.path)"
        download
        class="my-4 flex items-center gap-2 rounded-lg border p-3 text-sm font-medium hover:bg-muted/40"
    >
        <Paperclip class="size-4 text-primary" />
        {{ data.label }}
    </a>
</template>
