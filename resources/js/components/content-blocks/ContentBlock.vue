<script setup lang="ts">
import { computed } from 'vue';
import Heading from '@/components/content-blocks/Heading.vue';
import Image from '@/components/content-blocks/Image.vue';
import Text from '@/components/content-blocks/Text.vue';
import YoutubeVideo from '@/components/content-blocks/YoutubeVideo.vue';

const props = defineProps<{
    block: { id: number; type: string; data: Record<string, unknown> };
}>();

const components = {
    heading: Heading,
    text: Text,
    image: Image,
    youtube_video: YoutubeVideo,
};

const component = computed(
    () => components[props.block.type as keyof typeof components],
);
</script>

<template>
    <component :is="component" v-if="component" :data="block.data as any" />
</template>
