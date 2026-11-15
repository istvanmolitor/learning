<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { GripVertical, Plus } from '@lucide/vue';
import ContentBlockController from '@/actions/App/Http/Controllers/Admin/ContentBlockController';
import { Button } from '@/components/ui/button';
import {
    BLOCK_PALETTE,
    DEFAULT_BLOCK_DATA,
} from '@/lib/blocks/registry';
import type { BlockType, ContentBlock } from '@/lib/blocks/types';
import { cn } from '@/lib/utils';
import BlockEditor from './BlockEditor.vue';

const props = defineProps<{
    pageId: number;
    courseId: number;
    blocks: ContentBlock[];
}>();

const opts = { preserveScroll: true, preserveState: true };

// Block `data` is a heterogeneous bag validated server-side per type
// (BlockRegistry::validate()); Inertia's FormDataConvertible has no useful
// structural type for that, hence the casts below.
function addBlock(type: BlockType) {
    router.post(
        ContentBlockController.store.url(props.pageId),
        { type, data: DEFAULT_BLOCK_DATA[type] } as Record<string, any>,
        opts,
    );
    paletteOpen.value = false;
}

function saveBlock(block: ContentBlock, data: Record<string, unknown>) {
    router.put(
        ContentBlockController.update.url(block.id),
        { data } as Record<string, any>,
        opts,
    );
}

function deleteBlock(block: ContentBlock) {
    if (!window.confirm('Biztosan törlöd ezt a blokkot?')) return;
    router.delete(ContentBlockController.destroy.url(block.id), opts);
}

const paletteOpen = ref(false);
const families: { key: string; label: string }[] = [
    { key: 'structure', label: 'Szöveg és szerkezet' },
    { key: 'callout', label: 'Kiemelő doboz' },
    { key: 'media', label: 'Média' },
    { key: 'widget', label: 'Interaktív' },
];

/* ---------- drag & drop reorder ---------- */

const order = ref<number[]>(props.blocks.map((b) => b.id));
const dragId = ref<number | null>(null);

// Reconcile after a server round-trip (add/delete) instead of trusting the
// incoming prop order outright — that would undo any drag-reorder that
// hasn't been persisted to a *different* field yet.
watch(
    () => props.blocks.map((b) => b.id),
    (ids) => {
        const known = new Set(ids);
        order.value = [
            ...order.value.filter((id) => known.has(id)),
            ...ids.filter((id) => !order.value.includes(id)),
        ];
    },
);

function onDrop(targetIndex: number) {
    if (dragId.value === null) return;
    const from = order.value.indexOf(dragId.value);
    if (from === -1) return;
    const [moved] = order.value.splice(from, 1);
    order.value.splice(
        from < targetIndex ? targetIndex - 1 : targetIndex,
        0,
        moved,
    );
    dragId.value = null;
    router.post(
        ContentBlockController.reorder.url(props.pageId),
        { order: order.value },
        opts,
    );
}

function orderedBlocks() {
    return order.value
        .map((id) => props.blocks.find((b) => b.id === id))
        .filter((b): b is ContentBlock => !!b);
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div
            v-for="(block, index) in orderedBlocks()"
            :key="block.id"
            draggable="true"
            @dragstart="dragId = block.id"
            @dragover.prevent
            @drop.prevent="onDrop(index)"
        >
            <div class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                <GripVertical class="size-3.5 cursor-grab" />
                {{
                    BLOCK_PALETTE.find((p) => p.type === block.type)?.label ??
                    block.type
                }}
            </div>
            <BlockEditor
                :block="block"
                :course-id="courseId"
                @save="(data) => saveBlock(block, data)"
                @delete="deleteBlock(block)"
            />
        </div>

        <div class="relative">
            <Button
                type="button"
                variant="outline"
                class="w-full"
                @click="paletteOpen = !paletteOpen"
            >
                <Plus class="size-4" /> Blokk hozzáadása
            </Button>

            <div
                v-if="paletteOpen"
                class="absolute z-10 mt-1 w-full rounded-md border bg-popover p-2 shadow-md"
            >
                <div v-for="family in families" :key="family.key" class="mb-2">
                    <p class="px-1 text-xs font-semibold text-muted-foreground">
                        {{ family.label }}
                    </p>
                    <button
                        v-for="item in BLOCK_PALETTE.filter(
                            (p) => p.family === family.key,
                        )"
                        :key="item.type"
                        type="button"
                        :class="
                            cn(
                                'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent',
                            )
                        "
                        @click="addBlock(item.type)"
                    >
                        <span class="w-5 text-center">{{ item.icon }}</span>
                        {{ item.label }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
