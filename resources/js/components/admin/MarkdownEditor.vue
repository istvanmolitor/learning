<script setup lang="ts">
import { marked } from 'marked';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    name?: string;
    modelValue?: string;
    rows?: number;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const content = ref(props.modelValue ?? '');
const mode = ref<'write' | 'split' | 'preview'>('split');

watch(
    () => props.modelValue,
    (value) => {
        if (value !== undefined && value !== content.value) {
            content.value = value;
        }
    },
);

// Debounced render keeps the preview responsive on long documents.
const renderedHtml = ref('');
let renderTimer: ReturnType<typeof setTimeout> | undefined;

watch(
    content,
    (value) => {
        emit('update:modelValue', value);
        clearTimeout(renderTimer);
        renderTimer = setTimeout(() => {
            renderedHtml.value = marked.parse(value ?? '', {
                async: false,
            }) as string;
        }, 200);
    },
    { immediate: true },
);

const showEditor = computed(() => mode.value !== 'preview');
const showPreview = computed(() => mode.value !== 'write');
</script>

<template>
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-1 self-end">
            <Button
                v-for="option in [
                    { value: 'write', label: 'Szerkesztés' },
                    { value: 'split', label: 'Osztott' },
                    { value: 'preview', label: 'Előnézet' },
                ]"
                :key="option.value"
                type="button"
                size="sm"
                :variant="mode === option.value ? 'secondary' : 'ghost'"
                @click="mode = option.value as typeof mode"
            >
                {{ option.label }}
            </Button>
        </div>

        <div
            class="grid min-h-0 gap-3"
            :class="mode === 'split' ? 'lg:grid-cols-2' : 'grid-cols-1'"
        >
            <textarea
                v-show="showEditor"
                v-model="content"
                :name="name"
                :rows="rows ?? 24"
                spellcheck="false"
                class="w-full min-w-0 resize-y rounded-md border border-input bg-transparent px-3 py-2 font-mono text-sm leading-relaxed shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
            />
            <div
                v-show="showPreview"
                class="prose prose-sm prose-neutral max-w-none overflow-auto rounded-md border border-input px-4 py-3 dark:prose-invert"
                v-html="renderedHtml"
            />
        </div>
    </div>
</template>
