<script setup lang="ts">
import { ref } from 'vue';
import type { FlashcardsData } from '@/lib/blocks/types';

defineProps<{ data: FlashcardsData }>();

const flipped = ref<Set<number>>(new Set());

function toggle(index: number) {
    if (flipped.value.has(index)) {
        flipped.value.delete(index);
    } else {
        flipped.value.add(index);
    }
    flipped.value = new Set(flipped.value);
}
</script>

<template>
    <div class="my-4 flex flex-wrap gap-4">
        <button
            v-for="(card, i) in data.cards"
            :key="i"
            type="button"
            class="group h-32 w-44 cursor-pointer [perspective:800px]"
            @click="toggle(i)"
        >
            <div
                class="relative h-full w-full transition-transform duration-500 [transform-style:preserve-3d]"
                :class="{ '[transform:rotateY(180deg)]': flipped.has(i) }"
            >
                <div
                    class="absolute inset-0 flex items-center justify-center rounded-lg bg-gradient-to-br from-primary to-primary/70 p-3 text-center text-sm font-semibold text-primary-foreground [backface-visibility:hidden]"
                >
                    {{ card.front }}
                </div>
                <div
                    class="absolute inset-0 flex items-center justify-center rounded-lg border bg-card p-3 text-center text-sm [backface-visibility:hidden] [transform:rotateY(180deg)]"
                >
                    {{ card.back }}
                </div>
            </div>
        </button>
    </div>
</template>
