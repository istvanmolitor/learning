<script setup lang="ts">
import { ref } from 'vue';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import type { StepsData } from '@/lib/blocks/types';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = defineProps<{ data: StepsData }>();

const index = ref(0);

function move(delta: number) {
    index.value = Math.min(
        Math.max(index.value + delta, 0),
        props.data.steps.length - 1,
    );
}
</script>

<template>
    <div class="my-4 rounded-lg border p-4">
        <div class="min-h-24">
            <p class="mb-1 text-sm font-semibold text-primary">
                {{ index + 1 }}. {{ data.steps[index].title }}
            </p>
            <div
                class="prose prose-sm max-w-none dark:prose-invert"
                v-html="data.steps[index].html"
            />
        </div>

        <div class="mt-3 flex items-center justify-between">
            <Button
                variant="outline"
                size="sm"
                :disabled="index === 0"
                @click="move(-1)"
            >
                <ChevronLeft class="size-4" /> Előző
            </Button>

            <div class="flex items-center gap-1.5">
                <span
                    v-for="(_, i) in data.steps"
                    :key="i"
                    :class="
                        cn(
                            'size-2 rounded-full transition-colors',
                            i === index ? 'bg-primary' : 'bg-muted-foreground/30',
                        )
                    "
                />
            </div>

            <Button
                variant="outline"
                size="sm"
                :disabled="index === data.steps.length - 1"
                @click="move(1)"
            >
                Következő <ChevronRight class="size-4" />
            </Button>
        </div>
    </div>
</template>
