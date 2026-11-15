<script setup lang="ts">
import { ref } from 'vue';
import type { ImageData } from '@/lib/blocks/types';
import { mediaUrl } from '@/lib/blocks/media';
import { useRevealOnScroll } from '@/composables/useRevealOnScroll';
import { cn } from '@/lib/utils';
import Lightbox from '../Lightbox.vue';

const props = defineProps<{ data: ImageData }>();

const root = ref<HTMLElement | null>(null);
useRevealOnScroll(root);

const open = ref(false);
const url = mediaUrl(props.data.path);
</script>

<template>
    <figure
        ref="root"
        :class="
            cn(
                'my-4',
                data.size === 'wide' && '-mx-4 sm:-mx-8',
                data.size === 'small' && 'mx-auto max-w-sm',
            )
        "
    >
        <img
            :src="url"
            :alt="data.alt ?? ''"
            class="w-full cursor-zoom-in rounded-lg border"
            loading="lazy"
            @click="open = true"
        />
        <figcaption
            v-if="data.caption"
            class="mt-1.5 text-center text-sm text-muted-foreground"
        >
            {{ data.caption }}
        </figcaption>
    </figure>

    <Teleport to="body">
        <Lightbox
            v-if="open"
            :src="url"
            :alt="data.alt"
            @close="open = false"
        />
    </Teleport>
</template>
