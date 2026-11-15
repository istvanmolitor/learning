<script setup lang="ts">
import type { TextData } from '@/lib/blocks/types';

defineProps<{ data: TextData }>();
</script>

<template>
    <div
        class="prose prose-neutral max-w-none dark:prose-invert"
        v-html="data.html"
    />
</template>
