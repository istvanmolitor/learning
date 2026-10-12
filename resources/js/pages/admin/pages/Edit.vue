<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import ContentBlockController from '@/actions/App/Http/Controllers/Admin/ContentBlockController';
import LessonController from '@/actions/App/Http/Controllers/Admin/LessonController';
import PageController from '@/actions/App/Http/Controllers/Admin/PageController';
import ContentBlockForm from '@/components/admin/ContentBlockForm.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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

    <div class="flex flex-col gap-6 p-4">
        <div>
            <Link
                :href="LessonController.edit(lesson.id)"
                class="text-sm text-muted-foreground hover:underline"
            >
                &larr; {{ lesson.title }}
            </Link>
            <h1 class="text-xl font-semibold tracking-tight">
                {{ page ? 'Oldal szerkesztése' : 'Új oldal' }}
            </h1>
        </div>

        <Form
            v-bind="
                page
                    ? PageController.update.form(page.id)
                    : PageController.store.form(lesson.id)
            "
            class="flex max-w-xl flex-col gap-4"
            v-slot="{ errors, processing }"
        >
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

            <div class="grid gap-2">
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
                <Label for="narration_text">Felolvasandó szöveg (TTS)</Label>
                <textarea
                    id="narration_text"
                    name="narration_text"
                    rows="6"
                    class="border-input focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] md:text-sm dark:bg-input/30"
                    >{{ page?.narration_text }}</textarea
                >
                <InputError :message="errors.narration_text" />
            </div>

            <div class="grid gap-2">
                <audio
                    v-if="page?.narration_audio_path"
                    controls
                    class="w-full"
                    :src="`/storage/${page.narration_audio_path}`"
                />
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
                />
                <InputError :message="errors.narration_audio" />
            </div>

            <Button type="submit" :disabled="processing" class="w-fit"
                >Mentés</Button
            >
        </Form>

        <div v-if="page" class="flex max-w-xl flex-col gap-4">
            <h2 class="text-lg font-semibold tracking-tight">
                Tartalmi elemek
            </h2>

            <div
                v-for="block in page.content_blocks"
                :key="block.id"
                class="flex flex-col gap-2"
            >
                <ContentBlockForm
                    :action="ContentBlockController.update.form(block.id)"
                    :block="block"
                    submit-label="Mentés"
                />
                <Form v-bind="ContentBlockController.destroy.form(block.id)">
                    <Button type="submit" variant="destructive" size="sm"
                        >Blokk törlése</Button
                    >
                </Form>
            </div>

            <div class="flex flex-col gap-2">
                <h3 class="text-sm font-medium">Új tartalmi elem</h3>
                <ContentBlockForm
                    :action="ContentBlockController.store.form(page.id)"
                    submit-label="Hozzáadás"
                />
            </div>
        </div>
    </div>
</template>
