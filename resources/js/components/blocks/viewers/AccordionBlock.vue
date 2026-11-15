<script setup lang="ts">
import type { AccordionData } from '@/lib/blocks/types';

defineProps<{ data: AccordionData }>();
</script>

<template>
    <div class="my-4 flex flex-col gap-2">
        <details
            v-for="(item, i) in data.items"
            :key="i"
            class="group rounded-lg border p-3 open:pb-3"
        >
            <summary
                class="cursor-pointer list-none font-medium marker:content-none"
            >
                <span class="mr-1.5 inline-block transition-transform group-open:rotate-90">▸</span>
                {{ item.title }}
            </summary>
            <div
                class="prose prose-sm mt-2 max-w-none pl-5 dark:prose-invert"
                v-html="item.html"
            />
        </details>
    </div>
</template>
