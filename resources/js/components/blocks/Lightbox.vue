<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';
import { X } from '@lucide/vue';

const props = defineProps<{ src: string; alt?: string | null }>();
const emit = defineEmits<{ close: [] }>();

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') emit('close');
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-6"
        @click="emit('close')"
    >
        <button
            type="button"
            class="absolute top-4 right-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
            @click="emit('close')"
        >
            <X class="size-5" />
        </button>
        <img
            :src="props.src"
            :alt="props.alt ?? ''"
            class="max-h-full max-w-full rounded-md object-contain"
            @click.stop
        />
    </div>
</template>
