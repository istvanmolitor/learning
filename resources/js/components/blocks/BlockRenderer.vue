<script setup lang="ts">
import { computed } from 'vue';
import type { ContentBlock } from '@/lib/blocks/types';
import { VIEWER_REGISTRY } from '@/lib/blocks/registry';
import UnknownBlock from './viewers/UnknownBlock.vue';

const props = defineProps<{ blocks: ContentBlock[] }>();

const resolved = computed(() =>
    props.blocks.map((block) => ({
        block,
        component: VIEWER_REGISTRY[block.type] ?? null,
    })),
);
</script>

<template>
    <div class="flex flex-col">
        <template v-for="{ block, component } in resolved" :key="block.id">
            <component :is="component" v-if="component" :data="block.data" />
            <UnknownBlock v-else :type="block.type" />
        </template>
    </div>
</template>
