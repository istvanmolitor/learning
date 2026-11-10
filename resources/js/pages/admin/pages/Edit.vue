<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    AlignLeft,
    FileText,
    Heading,
    Image as ImageIcon,
    Plus,
    Trash2,
    Video,
} from '@lucide/vue';
import type { Component } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import ContentBlockController from '@/actions/App/Http/Controllers/Admin/ContentBlockController';
import LessonController from '@/actions/App/Http/Controllers/Admin/LessonController';
import PageController from '@/actions/App/Http/Controllers/Admin/PageController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import ContentBlockForm from '@/components/admin/ContentBlockForm.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    lesson: { id: number; title: string; course_id: number };
    page: {
        id: number;
        title: string;
        slug: string;
        order: number;
        narration_text: string | null;
        narration_audio_path: string | null;
        content_blocks: {
            id: number;
            type: string;
            data: Record<string, any>;
            order: number;
        }[];
    } | null;
}>();

const blockMeta: Record<string, { label: string; icon: Component }> = {
    heading: { label: 'Címsor', icon: Heading },
    text: { label: 'Szövegblokk', icon: AlignLeft },
    image: { label: 'Kép', icon: ImageIcon },
    youtube_video: { label: 'YouTube videó', icon: Video },
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin – Kurzusok', href: CourseController.index() },
        ],
    },
});
</script>

<template>
    <Head :title="page ? `Oldal szerkesztése – ${page.title}` : 'Új oldal'" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <AdminHeader
            :icon="FileText"
            :title="page ? 'Oldal szerkesztése' : 'Új oldal'"
            :description="page ? page.title : 'Új oldal a leckéhez.'"
            :back-href="LessonController.edit(lesson.id)"
            :back-label="lesson.title"
        />

        <Card>
            <CardHeader>
                <CardTitle>Alapadatok</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="
                        page
                            ? PageController.update.form(page.id)
                            : PageController.store.form(lesson.id)
                    "
                    class="flex flex-col gap-5"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="title">Cím</Label>
                            <Input
                                id="title"
                                name="title"
                                :default-value="page?.title"
                                required
                            />
                            <InputError :message="errors.title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="slug">Slug</Label>
                            <Input
                                id="slug"
                                name="slug"
                                :default-value="page?.slug"
                                required
                            />
                            <InputError :message="errors.slug" />
                        </div>
                    </div>

                    <div class="grid gap-2 sm:w-40">
                        <Label for="order">Sorrend</Label>
                        <Input
                            id="order"
                            type="number"
                            name="order"
                            :default-value="page?.order ?? 0"
                        />
                        <InputError :message="errors.order" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="narration_text"
                            >Felolvasandó szöveg (TTS)</Label
                        >
                        <textarea
                            id="narration_text"
                            name="narration_text"
                            rows="6"
                            class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            >{{ page?.narration_text }}</textarea
                        >
                        <InputError :message="errors.narration_text" />
                    </div>

                    <div
                        class="flex flex-col gap-3 rounded-lg border border-dashed border-input p-4"
                    >
                        <audio
                            v-if="page?.narration_audio_path"
                            controls
                            class="w-full"
                            :src="`/storage/${page.narration_audio_path}`"
                        />
                        <div class="grid gap-2">
                            <Label for="narration_audio"
                                >Felolvasás mp3
                                {{
                                    page?.narration_audio_path
                                        ? '(üresen hagyva marad a jelenlegi)'
                                        : ''
                                }}</Label
                            >
                            <input
                                id="narration_audio"
                                type="file"
                                name="narration_audio"
                                accept="audio/mpeg,.mp3"
                                class="text-sm"
                            />
                            <InputError :message="errors.narration_audio" />
                        </div>
                    </div>

                    <Button type="submit" :disabled="processing" class="w-fit"
                        >Mentés</Button
                    >
                </Form>
            </CardContent>
        </Card>

        <div v-if="page" class="flex flex-col gap-4">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold tracking-tight">
                    Tartalmi elemek
                </h2>
                <Badge variant="outline">{{
                    page.content_blocks.length
                }}</Badge>
            </div>

            <Card v-for="block in page.content_blocks" :key="block.id">
                <CardHeader class="flex items-center justify-between">
                    <CardTitle class="flex items-center gap-2 text-sm">
                        <span
                            class="flex size-7 items-center justify-center rounded-md bg-primary/10 text-primary"
                        >
                            <component
                                :is="blockMeta[block.type]?.icon"
                                class="size-4"
                            />
                        </span>
                        {{ blockMeta[block.type]?.label ?? block.type }}
                    </CardTitle>
                    <Form v-bind="ContentBlockController.destroy.form(block.id)">
                        <Button
                            type="submit"
                            variant="ghost"
                            size="sm"
                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                        >
                            <Trash2 class="size-3.5" />
                            Törlés
                        </Button>
                    </Form>
                </CardHeader>
                <CardContent>
                    <ContentBlockForm
                        :action="ContentBlockController.update.form(block.id)"
                        :block="block"
                        submit-label="Mentés"
                    />
                </CardContent>
            </Card>

            <Card class="border-dashed">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-sm">
                        <Plus class="size-4 text-muted-foreground" />
                        Új tartalmi elem
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <ContentBlockForm
                        :action="ContentBlockController.store.form(page.id)"
                        submit-label="Hozzáadás"
                    />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
