<script setup lang="ts">
import { onClickOutside, useDebounceFn } from '@vueuse/core';
import { Loader2, Search } from '@lucide/vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PageController from '@/actions/App/Http/Controllers/PageController';
import SearchController from '@/actions/App/Http/Controllers/SearchController';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type PageResult = {
    title: string;
    slug: string;
    lesson: { title: string; slug: string };
    course: { title: string; slug: string };
};

const query = ref('');
const results = ref<PageResult[]>([]);
const isOpen = ref(false);
const isLoading = ref(false);
const activeIndex = ref(-1);
const root = ref<HTMLElement | null>(null);

onClickOutside(root, () => {
    isOpen.value = false;
});

const runSearch = useDebounceFn(async (value: string) => {
    if (value.trim() === '') {
        results.value = [];
        isLoading.value = false;
        return;
    }

    const response = await fetch(
        SearchController.pages.url({ query: { q: value } }),
        { headers: { Accept: 'application/json' } },
    );
    const data = await response.json();

    results.value = data.pages;
    isLoading.value = false;
    activeIndex.value = -1;
}, 250);

function onInput() {
    isOpen.value = true;
    isLoading.value = true;
    runSearch(query.value);
}

function goToPage(result: PageResult) {
    isOpen.value = false;
    query.value = '';
    results.value = [];
    router.visit(
        PageController.show.url([
            result.course.slug,
            result.lesson.slug,
            result.slug,
        ]),
    );
}

function onKeydown(event: KeyboardEvent) {
    if (!isOpen.value || results.value.length === 0) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex.value = (activeIndex.value + 1) % results.value.length;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value =
            (activeIndex.value - 1 + results.value.length) %
            results.value.length;
    } else if (event.key === 'Enter' && activeIndex.value >= 0) {
        event.preventDefault();
        goToPage(results.value[activeIndex.value]);
    } else if (event.key === 'Escape') {
        isOpen.value = false;
    }
}
</script>

<template>
    <div ref="root" class="relative w-full max-w-sm">
        <div class="relative">
            <Search
                class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="query"
                type="search"
                placeholder="Oldalak keresése…"
                class="pl-8"
                @input="onInput"
                @focus="isOpen = true"
                @keydown="onKeydown"
            />
            <Loader2
                v-if="isLoading"
                class="absolute top-1/2 right-2.5 size-4 -translate-y-1/2 animate-spin text-muted-foreground"
            />
        </div>

        <div
            v-if="isOpen && query.trim() !== ''"
            class="absolute top-full z-50 mt-1 w-full overflow-hidden rounded-md border bg-popover shadow-md"
        >
            <ul v-if="results.length > 0" class="max-h-80 overflow-y-auto py-1">
                <li
                    v-for="(result, index) in results"
                    :key="`${result.course.slug}-${result.lesson.slug}-${result.slug}`"
                >
                    <button
                        type="button"
                        :class="
                            cn(
                                'flex w-full flex-col items-start gap-0.5 px-3 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground',
                                index === activeIndex &&
                                    'bg-accent text-accent-foreground',
                            )
                        "
                        @mouseenter="activeIndex = index"
                        @click="goToPage(result)"
                    >
                        <span class="font-medium">{{ result.title }}</span>
                        <span class="text-xs text-muted-foreground">
                            {{ result.course.title }} / {{ result.lesson.title }}
                        </span>
                    </button>
                </li>
            </ul>
            <p
                v-else-if="!isLoading"
                class="px-3 py-2 text-sm text-muted-foreground"
            >
                Nincs találat.
            </p>
        </div>
    </div>
</template>
