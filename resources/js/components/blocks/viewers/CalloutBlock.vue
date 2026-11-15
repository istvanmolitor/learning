<script setup lang="ts">
import { ref } from 'vue';
import { CALLOUT_VARIANTS } from '@/lib/blocks/calloutVariants';
import type { CalloutData } from '@/lib/blocks/types';
import { useRevealOnScroll } from '@/composables/useRevealOnScroll';
import { cn } from '@/lib/utils';

const props = defineProps<{ data: CalloutData }>();

const style = CALLOUT_VARIANTS[props.data.variant] ?? CALLOUT_VARIANTS.definicio;

const root = ref<HTMLElement | null>(null);
useRevealOnScroll(root);
</script>

<template>
    <div
        ref="root"
        :class="
            cn(
                'my-4 overflow-hidden rounded-lg border-[1.5px]',
                style.border,
                style.bg,
            )
        "
    >
        <div
            :class="
                cn(
                    'inline-block rounded-br-lg px-3 py-1 text-xs font-bold tracking-wide text-white',
                    style.badge,
                )
            "
        >
            <span>{{ style.icon }}</span> {{ style.label
            }}<template v-if="data.title"> · {{ data.title }}</template>
            <em v-if="data.title_en" class="font-normal opacity-90">
                ({{ data.title_en }})</em
            >
        </div>
        <div
            class="prose prose-sm prose-neutral max-w-none px-4 pt-1 pb-3 dark:prose-invert"
            v-html="data.html"
        />
    </div>
</template>
