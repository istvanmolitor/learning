<script setup lang="ts">
defineProps<{
    data: { path: string | null; alt: string | null; caption: string | null };
}>();
</script>

<template>
    <figure v-if="data.path" class="space-y-2">
        <img
            :src="`/storage/${data.path}`"
            :alt="data.alt ?? ''"
            class="max-w-full rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
        />
        <figcaption v-if="data.caption" class="text-sm text-muted-foreground">
            {{ data.caption }}
        </figcaption>
    </figure>
</template>
