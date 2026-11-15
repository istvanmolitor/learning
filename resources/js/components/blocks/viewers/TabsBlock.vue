<script setup lang="ts">
import { ref } from 'vue';
import type { TabsData } from '@/lib/blocks/types';
import { cn } from '@/lib/utils';

defineProps<{ data: TabsData }>();

const active = ref(0);
</script>

<template>
    <div class="my-4 rounded-lg border">
        <div class="flex flex-wrap gap-1 border-b bg-muted/30 p-1.5">
            <button
                v-for="(tab, i) in data.tabs"
                :key="i"
                type="button"
                :class="
                    cn(
                        'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                        active === i
                            ? 'bg-background shadow-sm'
                            : 'text-muted-foreground hover:bg-background/60',
                    )
                "
                @click="active = i"
            >
                {{ tab.title }}
            </button>
        </div>
        <div
            class="prose prose-sm max-w-none p-4 dark:prose-invert"
            v-html="data.tabs[active].html"
        />
    </div>
</template>
