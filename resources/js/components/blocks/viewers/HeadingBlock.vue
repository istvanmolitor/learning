<script setup lang="ts">
import { computed } from 'vue';
import type { HeadingData } from '@/lib/blocks/types';

const props = defineProps<{ data: HeadingData }>();

const tag = computed(() => `h${props.data.level}` as const);
const sizeClass = computed(() =>
    props.data.level === 2
        ? 'text-xl font-semibold'
        : props.data.level === 3
          ? 'text-lg font-semibold'
          : 'text-base font-semibold',
);
</script>

<template>
    <component :is="tag" :class="['tracking-tight', sizeClass]">{{
        data.text
    }}</component>
</template>
