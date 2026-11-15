<script setup lang="ts">
import { Volume2 } from '@lucide/vue';
import type { AudioData } from '@/lib/blocks/types';
import { mediaUrl } from '@/lib/blocks/media';

defineProps<{ data: AudioData }>();
</script>

<template>
    <div class="my-4 rounded-lg border p-3">
        <div class="mb-2 flex items-center gap-2 text-sm font-medium">
            <Volume2 class="size-4 text-primary" />
            {{ data.title ?? 'Hanganyag' }}
        </div>
        <audio controls class="w-full" :src="mediaUrl(data.path)" />
        <details v-if="data.transcript" class="mt-2 text-sm">
            <summary class="cursor-pointer text-muted-foreground">
                Átirat
            </summary>
            <p class="mt-1 whitespace-pre-line">{{ data.transcript }}</p>
        </details>
    </div>
</template>
