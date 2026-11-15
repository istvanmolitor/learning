<script setup lang="ts">
defineProps<{ type?: string }>();
</script>

<template>
    <div
        class="my-4 rounded-md border border-dashed p-3 text-sm text-muted-foreground"
    >
        Nem támogatott tartalomblokk<template v-if="type"> ({{ type }})</template>.
    </div>
</template>
