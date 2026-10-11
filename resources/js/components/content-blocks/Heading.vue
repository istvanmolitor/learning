<script setup lang="ts">
defineProps<{
    data: { text: string; level: number };
}>();
</script>

<template>
    <component
        :is="`h${data.level}`"
        class="font-semibold tracking-tight"
        :class="{
            'text-3xl': data.level === 1,
            'text-2xl': data.level === 2,
            'text-xl': data.level === 3,
            'text-lg': data.level >= 4,
        }"
    >
        {{ data.text }}
    </component>
</template>
