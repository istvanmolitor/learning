<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import type { Component } from 'vue';

defineProps<{
    icon?: Component;
    title: string;
    description?: string;
    backHref?: InertiaLinkProps['href'];
    backLabel?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="flex flex-col gap-2">
            <Link
                v-if="backHref"
                :href="backHref"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-primary"
            >
                <ArrowLeft class="size-3.5" />
                {{ backLabel }}
            </Link>
            <div class="flex items-center gap-3">
                <div
                    v-if="icon"
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <component :is="icon" class="size-5" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <slot />
        </div>
    </div>
</template>
