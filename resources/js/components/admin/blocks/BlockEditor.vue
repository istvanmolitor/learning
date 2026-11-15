<script setup lang="ts">
import { reactive, watch } from 'vue';
import { Plus, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CALLOUT_VARIANTS } from '@/lib/blocks/calloutVariants';
import type { ContentBlock } from '@/lib/blocks/types';
import MediaPicker from './MediaPicker.vue';
import RichField from './RichField.vue';

const props = defineProps<{ block: ContentBlock; courseId: number }>();
const emit = defineEmits<{
    save: [data: Record<string, unknown>];
    delete: [];
}>();

// block.data arrives as a Vue reactive proxy (it's part of the Inertia page
// props) — structuredClone() rejects those, so a JSON round-trip is used
// instead. Block data is always JSON-safe anyway (it came from the server
// as JSON in the first place).
function clone<T>(value: T): T {
    return JSON.parse(JSON.stringify(value));
}

// A local, editable clone — the parent only hears about changes on explicit
// save, so a half-finished edit never races a sibling block's save.
const form = reactive<Record<string, any>>(clone(props.block.data));

watch(
    () => props.block.id,
    () => Object.assign(form, clone(props.block.data)),
);

function save() {
    emit('save', clone(form));
}

function addItem(key: string, item: Record<string, unknown>) {
    form[key] ??= [];
    form[key].push(item);
}

function removeItem(key: string, index: number) {
    form[key].splice(index, 1);
}
</script>

<template>
    <div class="flex flex-col gap-3 rounded-lg border p-3">
        <!-- text -->
        <RichField
            v-if="block.type === 'text'"
            label="Szöveg"
            name="html"
            v-model="form.html"
        />

        <!-- heading -->
        <div v-else-if="block.type === 'heading'" class="flex gap-2">
            <select
                v-model.number="form.level"
                class="rounded-md border border-input bg-transparent px-2 text-sm"
            >
                <option :value="2">H2</option>
                <option :value="3">H3</option>
                <option :value="4">H4</option>
            </select>
            <Input v-model="form.text" placeholder="Cím szövege" class="flex-1" />
        </div>

        <!-- quote -->
        <div v-else-if="block.type === 'quote'" class="grid gap-2">
            <RichField label="Idézet" name="html" v-model="form.html" />
            <Input v-model="form.source" placeholder="Forrás (opcionális)" />
        </div>

        <!-- divider -->
        <div v-else-if="block.type === 'divider'" class="flex gap-2">
            <select
                v-model="form.variant"
                class="rounded-md border border-input bg-transparent px-2 py-1 text-sm"
            >
                <option value="line">Vonal</option>
                <option value="space">Térköz</option>
            </select>
        </div>

        <!-- table -->
        <div v-else-if="block.type === 'table'" class="grid gap-2">
            <Label>Fejlécek (vesszővel elválasztva)</Label>
            <Input
                :model-value="(form.headers ?? []).join(', ')"
                @update:model-value="
                    form.headers = ($event as string)
                        .split(',')
                        .map((v) => v.trim())
                "
            />
            <Label>Sorok (soronként, cellák vesszővel)</Label>
            <textarea
                :value="(form.rows ?? []).map((r: string[]) => r.join(', ')).join('\n')"
                rows="4"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 font-mono text-sm"
                @change="
                    form.rows = ($event.target as HTMLTextAreaElement).value
                        .split('\n')
                        .filter((l) => l.trim() !== '')
                        .map((l) => l.split(',').map((c) => c.trim()))
                "
            />
        </div>

        <!-- accordion / tabs: title + rich body list -->
        <div
            v-else-if="block.type === 'accordion' || block.type === 'tabs'"
            class="grid gap-3"
        >
            <div
                v-for="(item, i) in form[block.type === 'accordion' ? 'items' : 'tabs']"
                :key="i"
                class="grid gap-2 rounded-md border p-2"
            >
                <div class="flex items-center gap-2">
                    <Input v-model="item.title" placeholder="Cím" class="flex-1" />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        @click="
                            removeItem(
                                block.type === 'accordion' ? 'items' : 'tabs',
                                i,
                            )
                        "
                    >
                        <Trash2 class="size-3.5" />
                    </Button>
                </div>
                <RichField label="Tartalom" :name="`item-${i}`" v-model="item.html" />
            </div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="
                    addItem(block.type === 'accordion' ? 'items' : 'tabs', {
                        title: '',
                        html: '',
                    })
                "
            >
                <Plus class="size-3.5" /> Szakasz hozzáadása
            </Button>
        </div>

        <!-- callout -->
        <div v-else-if="block.type === 'callout'" class="grid gap-2">
            <select
                v-model="form.variant"
                class="w-fit rounded-md border border-input bg-transparent px-2 py-1 text-sm"
            >
                <option
                    v-for="(style, key) in CALLOUT_VARIANTS"
                    :key="key"
                    :value="key"
                >
                    {{ style.icon }} {{ style.label }}
                </option>
            </select>
            <div class="flex gap-2">
                <Input v-model="form.title" placeholder="Cím (opcionális)" class="flex-1" />
                <Input
                    v-model="form.title_en"
                    placeholder="Angol megfelelő (opcionális)"
                    class="flex-1"
                />
            </div>
            <RichField label="Tartalom" name="html" v-model="form.html" />
        </div>

        <!-- image -->
        <div v-else-if="block.type === 'image'" class="grid gap-2">
            <MediaPicker
                v-model="form.path"
                :course-id="courseId"
                accept="image/*"
            />
            <Input v-model="form.alt" placeholder="Alt szöveg" />
            <Input v-model="form.caption" placeholder="Képaláírás (opcionális)" />
            <select
                v-model="form.size"
                class="w-fit rounded-md border border-input bg-transparent px-2 py-1 text-sm"
            >
                <option value="small">Kicsi</option>
                <option value="normal">Normál</option>
                <option value="wide">Széles</option>
            </select>
        </div>

        <!-- gallery -->
        <div v-else-if="block.type === 'gallery'" class="grid gap-3">
            <select
                v-model="form.layout"
                class="w-fit rounded-md border border-input bg-transparent px-2 py-1 text-sm"
            >
                <option value="grid">Rács</option>
                <option value="carousel">Lapozható</option>
            </select>
            <div
                v-for="(item, i) in form.items"
                :key="i"
                class="grid gap-2 rounded-md border p-2"
            >
                <MediaPicker
                    v-model="item.path"
                    :course-id="courseId"
                    accept="image/*"
                />
                <div class="flex gap-2">
                    <Input v-model="item.alt" placeholder="Alt" class="flex-1" />
                    <Input
                        v-model="item.caption"
                        placeholder="Aláírás"
                        class="flex-1"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        @click="removeItem('items', i)"
                    >
                        <Trash2 class="size-3.5" />
                    </Button>
                </div>
            </div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="addItem('items', { path: '', alt: '', caption: '' })"
            >
                <Plus class="size-3.5" /> Kép hozzáadása
            </Button>
        </div>

        <!-- video -->
        <div v-else-if="block.type === 'video'" class="grid gap-2">
            <select
                v-model="form.source"
                class="w-fit rounded-md border border-input bg-transparent px-2 py-1 text-sm"
            >
                <option value="youtube">YouTube</option>
                <option value="vimeo">Vimeo</option>
                <option value="file">Feltöltött fájl</option>
            </select>
            <Input
                v-if="form.source === 'youtube' || form.source === 'vimeo'"
                v-model="form.video_id"
                placeholder="Videó ID (pl. a YouTube URL embed/ utáni része)"
            />
            <MediaPicker
                v-else
                v-model="form.path"
                :course-id="courseId"
                accept="video/*"
            />
            <Input v-model="form.title" placeholder="Cím (opcionális)" />
        </div>

        <!-- audio -->
        <div v-else-if="block.type === 'audio'" class="grid gap-2">
            <MediaPicker v-model="form.path" :course-id="courseId" accept="audio/*" />
            <Input v-model="form.title" placeholder="Cím (opcionális)" />
            <textarea
                v-model="form.transcript"
                rows="3"
                placeholder="Átirat (opcionális)"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
            />
        </div>

        <!-- file -->
        <div v-else-if="block.type === 'file'" class="grid gap-2">
            <MediaPicker v-model="form.path" :course-id="courseId" />
            <Input v-model="form.label" placeholder="Gomb felirata" />
        </div>

        <!-- chart -->
        <div v-else-if="block.type === 'chart'" class="grid gap-3">
            <div class="flex gap-2">
                <select
                    v-model="form.variant"
                    class="rounded-md border border-input bg-transparent px-2 py-1 text-sm"
                >
                    <option value="bar">Oszlop</option>
                    <option value="line">Vonal</option>
                    <option value="pie">Kördiagram</option>
                </select>
                <Input v-model="form.title" placeholder="Cím (opcionális)" class="flex-1" />
            </div>
            <Label>Címkék (vesszővel elválasztva)</Label>
            <Input
                :model-value="(form.labels ?? []).join(', ')"
                @update:model-value="
                    form.labels = ($event as string).split(',').map((v) => v.trim())
                "
            />
            <div
                v-for="(series, i) in form.series"
                :key="i"
                class="flex items-center gap-2"
            >
                <Input v-model="series.name" placeholder="Sorozat neve" class="w-32" />
                <Input
                    :model-value="(series.data ?? []).join(', ')"
                    placeholder="Értékek, vesszővel"
                    class="flex-1"
                    @update:model-value="
                        series.data = ($event as string)
                            .split(',')
                            .map((v) => Number(v.trim()) || 0)
                    "
                />
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    @click="removeItem('series', i)"
                >
                    <Trash2 class="size-3.5" />
                </Button>
            </div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="addItem('series', { name: '', data: [] })"
            >
                <Plus class="size-3.5" /> Sorozat hozzáadása
            </Button>
        </div>

        <!-- flashcards -->
        <div v-else-if="block.type === 'flashcards'" class="grid gap-2">
            <div
                v-for="(card, i) in form.cards"
                :key="i"
                class="flex items-center gap-2"
            >
                <Input v-model="card.front" placeholder="Elülső oldal" class="flex-1" />
                <Input v-model="card.back" placeholder="Hátoldal" class="flex-1" />
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    @click="removeItem('cards', i)"
                >
                    <Trash2 class="size-3.5" />
                </Button>
            </div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="addItem('cards', { front: '', back: '' })"
            >
                <Plus class="size-3.5" /> Kártya hozzáadása
            </Button>
        </div>

        <!-- steps -->
        <div v-else-if="block.type === 'steps'" class="grid gap-3">
            <div
                v-for="(step, i) in form.steps"
                :key="i"
                class="grid gap-2 rounded-md border p-2"
            >
                <div class="flex items-center gap-2">
                    <Input v-model="step.title" placeholder="Lépés címe" class="flex-1" />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        @click="removeItem('steps', i)"
                    >
                        <Trash2 class="size-3.5" />
                    </Button>
                </div>
                <RichField label="Leírás" :name="`step-${i}`" v-model="step.html" />
            </div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="addItem('steps', { title: '', html: '' })"
            >
                <Plus class="size-3.5" /> Lépés hozzáadása
            </Button>
        </div>

        <div class="flex items-center justify-between border-t pt-2">
            <Button type="button" size="sm" @click="save">Blokk mentése</Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                @click="emit('delete')"
            >
                <Trash2 class="size-3.5" /> Törlés
            </Button>
        </div>
    </div>
</template>
