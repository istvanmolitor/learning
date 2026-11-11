<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    FilePlus2,
    FileWarning,
    Pencil,
    Scissors,
    Trash2,
    UploadCloud,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ChangeSet = { create: string[]; update: string[]; delete: string[] };

const props = defineProps<{
    plan: {
        format: string;
        course: {
            title: string;
            slug: string;
            description: string | null;
            lesson_count: number;
            page_count: number;
            question_count: number;
        };
        existing_course: { id: number; title: string } | null;
        changes: { lessons: ChangeSet; pages: ChangeSet } | null;
        warnings: string[];
        long_pages: {
            lesson_slug: string;
            page_slug: string;
            label: string;
            splittable: boolean;
        }[];
    } | null;
    token: string | null;
}>();

const formatLabels: Record<string, string> = {
    'zip-manifest': 'Teljes kurzuscsomag (course.json + tartalom)',
    'manifest-only': 'Csak szerkezet (course.json, tartalom nélkül)',
    markdown: 'Markdown fájlok (szerkezet fájlnevekből)',
};

const mode = ref<'auto' | 'new'>('auto');
const selectedFileNames = ref<string[]>([]);

const deletions = computed(() => {
    if (!props.plan?.changes) return [];
    return [
        ...props.plan.changes.lessons.delete.map((t) => `Lecke: ${t}`),
        ...props.plan.changes.pages.delete.map((t) => `Oldal: ${t}`),
    ];
});

const splittableLongPages = computed(
    () => props.plan?.long_pages.filter((p) => p.splittable) ?? [],
);

function onFilesSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    selectedFileNames.value = Array.from(input.files ?? []).map((f) => f.name);
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin – Kurzusok', href: CourseController.index() },
            { title: 'Importálás', href: CourseController.importCreate() },
        ],
    },
});
</script>

<template>
    <Head title="Admin – Kurzus importálása" />

    <div class="flex flex-col gap-6 p-4">
        <AdminHeader
            :icon="UploadCloud"
            title="Kurzus importálása"
            :description="
                plan
                    ? 'Nézd át, mit fog csinálni az import, és erősítsd meg.'
                    : 'Tölts fel egy kurzuscsomagot — a rendszer felismeri, mit kapott.'
            "
            :back-href="CourseController.index()"
            back-label="Vissza a kurzusokhoz"
        />

        <!-- 1. lépés: feltöltés -->
        <Card v-if="!plan" class="max-w-2xl p-6">
            <Form
                v-bind="CourseController.importAnalyze.form()"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-4"
            >
                <label
                    class="flex cursor-pointer flex-col items-center gap-2 rounded-lg border border-dashed border-input px-6 py-14 text-center text-sm text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                >
                    <UploadCloud class="size-10" />
                    <span class="text-base"
                        >Húzd ide a fájlokat, vagy kattints a tallózáshoz</span
                    >
                    <span class="max-w-md text-xs">
                        Elfogadott: exportált kurzus <strong>zip</strong>, csak
                        <strong>.md</strong> oldalfájlok (akár több is), vagy
                        önálló <strong>course.json</strong>. Nem kell módot
                        választani — az elemzés megmutatja, mi fog történni.
                    </span>
                    <input
                        type="file"
                        name="files[]"
                        accept=".zip,.md,.json,application/zip,application/json,text/markdown"
                        multiple
                        class="hidden"
                        @change="onFilesSelected"
                    />
                </label>

                <div
                    v-if="selectedFileNames.length"
                    class="flex flex-wrap gap-1"
                >
                    <Badge
                        v-for="name in selectedFileNames"
                        :key="name"
                        variant="secondary"
                        >{{ name }}</Badge
                    >
                </div>

                <InputError :message="errors.files" />

                <Button type="submit" :disabled="processing" class="self-start">
                    Elemzés
                </Button>
            </Form>
        </Card>

        <!-- 2. lépés: terv + megerősítés -->
        <template v-else>
            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        {{ plan.course.title }}
                        <Badge variant="outline">{{ plan.course.slug }}</Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-3 text-sm">
                    <p class="text-muted-foreground">
                        {{ formatLabels[plan.format] ?? plan.format }}
                    </p>
                    <div class="flex gap-4">
                        <span
                            ><strong>{{ plan.course.lesson_count }}</strong>
                            lecke</span
                        >
                        <span
                            ><strong>{{ plan.course.page_count }}</strong>
                            oldal</span
                        >
                        <span
                            ><strong>{{ plan.course.question_count }}</strong>
                            kérdés</span
                        >
                    </div>
                    <p
                        v-if="!plan.course.description"
                        class="flex items-center gap-1.5 text-muted-foreground"
                    >
                        <FileWarning class="size-4" /> Nincs leírás a csomagban
                        — importálás után érdemes kitölteni.
                    </p>
                </CardContent>
            </Card>

            <Card
                v-if="plan.existing_course"
                class="max-w-2xl border-amber-500/50"
            >
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-amber-600">
                        <AlertTriangle class="size-5" />
                        Meglévő kurzust frissít
                    </CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-3 text-sm">
                    <p>
                        Már létezik kurzus ezzel a slug-gal:
                        <strong>{{ plan.existing_course.title }}</strong
                        >. Frissítéskor a csomag tartalma lesz a mérvadó.
                    </p>

                    <div v-if="plan.changes" class="grid gap-2 sm:grid-cols-3">
                        <div class="rounded-md border p-3">
                            <p
                                class="mb-1 flex items-center gap-1 font-medium text-green-600"
                            >
                                <FilePlus2 class="size-4" /> Létrejön ({{
                                    plan.changes.pages.create.length
                                }})
                            </p>
                            <p
                                v-for="item in plan.changes.pages.create"
                                :key="item"
                                class="truncate text-xs text-muted-foreground"
                            >
                                {{ item }}
                            </p>
                        </div>
                        <div class="rounded-md border p-3">
                            <p
                                class="mb-1 flex items-center gap-1 font-medium"
                            >
                                <Pencil class="size-4" /> Frissül ({{
                                    plan.changes.pages.update.length
                                }})
                            </p>
                            <p
                                v-for="item in plan.changes.pages.update"
                                :key="item"
                                class="truncate text-xs text-muted-foreground"
                            >
                                {{ item }}
                            </p>
                        </div>
                        <div class="rounded-md border border-destructive/40 p-3">
                            <p
                                class="mb-1 flex items-center gap-1 font-medium text-destructive"
                            >
                                <Trash2 class="size-4" /> Törlődik ({{
                                    deletions.length
                                }})
                            </p>
                            <p
                                v-for="item in deletions"
                                :key="item"
                                class="truncate text-xs text-muted-foreground"
                            >
                                {{ item }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card
                v-if="plan.warnings.length || plan.long_pages.length"
                class="max-w-2xl"
            >
                <CardHeader>
                    <CardTitle class="text-base">Figyelmeztetések</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-2 text-sm">
                    <p
                        v-for="warning in plan.warnings"
                        :key="warning"
                        class="flex items-start gap-1.5 text-destructive"
                    >
                        <AlertTriangle class="mt-0.5 size-4 shrink-0" />
                        {{ warning }}
                    </p>
                    <p
                        v-for="longPage in plan.long_pages"
                        :key="longPage.label"
                        class="flex items-start gap-1.5 text-amber-600"
                    >
                        <Scissors class="mt-0.5 size-4 shrink-0" />
                        Túl hosszú oldal: {{ longPage.label }}
                        <template v-if="!longPage.splittable">
                            (nem bontható automatikusan — nincsenek ##
                            címsorok)</template
                        >
                    </p>
                </CardContent>
            </Card>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <CheckCircle2 class="size-5" /> Megerősítés
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="CourseController.importConfirm.form()"
                        v-slot="{ errors, processing }"
                        class="flex flex-col gap-4 text-sm"
                    >
                        <input type="hidden" name="token" :value="token" />

                        <template v-if="plan.existing_course">
                            <div class="flex flex-col gap-2">
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="mode"
                                        type="radio"
                                        name="mode"
                                        value="auto"
                                    />
                                    A meglévő kurzus frissítése
                                </label>
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="mode"
                                        type="radio"
                                        name="mode"
                                        value="new"
                                    />
                                    Új kurzus létrehozása más slug-gal
                                </label>
                                <div v-if="mode === 'new'" class="grid gap-2 pl-6 sm:w-80">
                                    <Label for="new_slug">Új slug</Label>
                                    <Input
                                        id="new_slug"
                                        name="new_slug"
                                        :default-value="`${plan.course.slug}-2`"
                                    />
                                    <InputError :message="errors.new_slug" />
                                </div>
                            </div>

                            <label
                                v-if="deletions.length && mode === 'auto'"
                                class="flex items-start gap-2 rounded-md border border-destructive/40 p-3"
                            >
                                <input
                                    type="checkbox"
                                    name="confirm_deletes"
                                    value="1"
                                    class="mt-0.5"
                                />
                                <span>
                                    Tudomásul veszem, hogy a csomagban nem
                                    szereplő {{ deletions.length }} elem
                                    <strong>véglegesen törlődik</strong> (a
                                    hozzájuk tartozó tanulói haladással együtt).
                                </span>
                            </label>
                            <InputError :message="errors.confirm_deletes" />
                        </template>
                        <input
                            v-else
                            type="hidden"
                            name="mode"
                            value="auto"
                        />

                        <label
                            v-if="splittableLongPages.length"
                            class="flex items-start gap-2 rounded-md border p-3"
                        >
                            <input
                                type="checkbox"
                                name="split_long_pages"
                                value="1"
                                checked
                                class="mt-0.5"
                            />
                            <span>
                                A túl hosszú oldalak ({{
                                    splittableLongPages.length
                                }}
                                db) automatikus szétbontása több oldalra a
                                <code>##</code> címsorok mentén.
                            </span>
                        </label>

                        <InputError :message="errors.files" />

                        <div class="flex gap-2">
                            <Button type="submit" :disabled="processing">
                                Importálás
                            </Button>
                        </div>
                    </Form>

                    <Form
                        v-bind="CourseController.importDiscard.form()"
                        class="mt-2"
                    >
                        <input type="hidden" name="token" :value="token" />
                        <Button type="submit" variant="ghost" size="sm">
                            Mégse, másik fájlt töltök fel
                        </Button>
                    </Form>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
