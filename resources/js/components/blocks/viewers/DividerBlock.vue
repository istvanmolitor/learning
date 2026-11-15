<script setup lang="ts">
import type { DividerData } from '@/lib/blocks/types';

defineProps<{ data: DividerData }>();
</script>

<template>
    <hr v-if="data.variant === 'line'" class="my-6 border-border" />
    <div v-else class="h-6" />
</template>
