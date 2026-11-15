<script setup lang="ts">
import type { QuoteData } from '@/lib/blocks/types';

defineProps<{ data: QuoteData }>();
</script>

<template>
    <blockquote
        class="my-4 border-l-4 border-primary/40 pl-4 italic text-muted-foreground"
    >
        <div class="prose prose-sm max-w-none dark:prose-invert" v-html="data.html" />
        <cite v-if="data.source" class="mt-1 block text-sm not-italic"
            >— {{ data.source }}</cite
        >
    </blockquote>
</template>
