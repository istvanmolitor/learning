<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { RouteFormDefinition } from '@/wayfinder';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    action: RouteFormDefinition<'post' | 'put'>;
    block?: {
        type: string;
        data: Record<string, any>;
        order: number;
    };
    submitLabel: string;
}>();

const type = ref(props.block?.type ?? 'heading');
</script>

<template>
    <Form
        v-bind="action"
        class="flex flex-col gap-3 rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label>Típus</Label>
            <select
                v-model="type"
                name="type"
                class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <option value="heading">Címsor</option>
                <option value="text">Szövegblokk</option>
                <option value="image">Kép</option>
                <option value="youtube_video">YouTube videó</option>
            </select>
            <InputError :message="errors.type" />
        </div>

        <template v-if="type === 'heading'">
            <div class="grid gap-2">
                <Label>Szöveg</Label>
                <Input name="text" :default-value="block?.data.text" required />
                <InputError :message="errors.text" />
            </div>
            <div class="grid gap-2">
                <Label>Szint (1-6)</Label>
                <Input
                    type="number"
                    name="level"
                    min="1"
                    max="6"
                    :default-value="block?.data.level ?? 2"
                    required
                />
                <InputError :message="errors.level" />
            </div>
        </template>

        <template v-else-if="type === 'text'">
            <div class="grid gap-2">
                <Label>HTML tartalom</Label>
                <textarea
                    name="html"
                    rows="4"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >{{ block?.data.html }}</textarea>
                <InputError :message="errors.html" />
            </div>
        </template>

        <template v-else-if="type === 'image'">
            <img
                v-if="block?.data.path"
                :src="`/storage/${block.data.path}`"
                class="max-h-32 rounded-md border border-sidebar-border/70 dark:border-sidebar-border"
            />
            <div class="grid gap-2">
                <Label
                    >Kép fájl
                    {{
                        block ? '(üresen hagyva marad a jelenlegi)' : ''
                    }}</Label
                >
                <input
                    type="file"
                    name="image"
                    accept="image/*"
                    :required="!block"
                />
                <InputError :message="errors.image" />
            </div>
            <div class="grid gap-2">
                <Label>Alt szöveg</Label>
                <Input name="alt" :default-value="block?.data.alt" />
                <InputError :message="errors.alt" />
            </div>
            <div class="grid gap-2">
                <Label>Felirat</Label>
                <Input name="caption" :default-value="block?.data.caption" />
                <InputError :message="errors.caption" />
            </div>
        </template>

        <template v-else-if="type === 'youtube_video'">
            <div class="grid gap-2">
                <Label>YouTube videó ID</Label>
                <Input
                    name="video_id"
                    :default-value="block?.data.video_id"
                    required
                />
                <InputError :message="errors.video_id" />
            </div>
            <div class="grid gap-2">
                <Label>Cím (opcionális)</Label>
                <Input name="title" :default-value="block?.data.title" />
                <InputError :message="errors.title" />
            </div>
        </template>

        <div class="grid gap-2">
            <Label>Sorrend</Label>
            <Input
                type="number"
                name="order"
                :default-value="block?.order ?? 0"
            />
        </div>

        <Button type="submit" :disabled="processing" class="w-fit">{{
            submitLabel
        }}</Button>
    </Form>
</template>
