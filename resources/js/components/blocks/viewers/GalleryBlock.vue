<script setup lang="ts">
import { ref } from 'vue';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import type { GalleryData } from '@/lib/blocks/types';
import { mediaUrl } from '@/lib/blocks/media';
import Lightbox from '../Lightbox.vue';

const props = defineProps<{ data: GalleryData }>();

const lightboxIndex = ref<number | null>(null);
const carouselIndex = ref(0);

function next() {
    carouselIndex.value = (carouselIndex.value + 1) % props.data.items.length;
}
function prev() {
    carouselIndex.value =
        (carouselIndex.value - 1 + props.data.items.length) %
        props.data.items.length;
}
</script>

<template>
    <div class="my-4">
        <div
            v-if="data.layout === 'carousel'"
            class="relative overflow-hidden rounded-lg border"
        >
            <img
                :src="mediaUrl(data.items[carouselIndex].path)"
                :alt="data.items[carouselIndex].alt ?? ''"
                class="aspect-video w-full cursor-zoom-in object-cover"
                @click="lightboxIndex = carouselIndex"
            />
            <figcaption
                v-if="data.items[carouselIndex].caption"
                class="bg-black/50 px-3 py-1.5 text-sm text-white"
            >
                {{ data.items[carouselIndex].caption }}
            </figcaption>
            <template v-if="data.items.length > 1">
                <button
                    type="button"
                    class="absolute top-1/2 left-2 -translate-y-1/2 rounded-full bg-black/40 p-1.5 text-white hover:bg-black/60"
                    @click="prev"
                >
                    <ChevronLeft class="size-4" />
                </button>
                <button
                    type="button"
                    class="absolute top-1/2 right-2 -translate-y-1/2 rounded-full bg-black/40 p-1.5 text-white hover:bg-black/60"
                    @click="next"
                >
                    <ChevronRight class="size-4" />
                </button>
            </template>
        </div>

        <div v-else class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            <figure v-for="(item, i) in data.items" :key="i" class="m-0">
                <img
                    :src="mediaUrl(item.path)"
                    :alt="item.alt ?? ''"
                    class="aspect-square w-full cursor-zoom-in rounded-md border object-cover"
                    loading="lazy"
                    @click="lightboxIndex = i"
                />
            </figure>
        </div>
    </div>

    <Teleport to="body">
        <Lightbox
            v-if="lightboxIndex !== null"
            :src="mediaUrl(data.items[lightboxIndex].path)"
            :alt="data.items[lightboxIndex].alt"
            @close="lightboxIndex = null"
        />
    </Teleport>
</template>
