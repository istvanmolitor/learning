<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Braces, FileText, UploadCloud } from '@lucide/vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

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
            description="Tölts fel egy korábban exportált zip-et, vagy .md oldalfájlokat egy új kurzushoz."
            :back-href="CourseController.index()"
            back-label="Vissza a kurzusokhoz"
        />

        <Card class="max-w-xl p-6">
            <h2 class="mb-1 font-medium">ZIP import</h2>
            <p class="mb-4 text-sm text-muted-foreground">
                Ha a zip course.json-t tartalmaz, és a benne lévő kurzus
                slug-ja egyezik egy meglévő kurzussal, azt frissítjük (a
                zipben már nem szereplő leckék/oldalak/kérdések törlődnek). Ha
                nincs course.json a zipben, csak mappákba rendezett .md
                fájlokat tartalmaz — minden mappa egy lecke lesz, benne a .md
                fájlok az oldalak —, egy új kurzus jön létre, amit utólag az
                admin felületen kell majd kitölteni (cím, leírás, stb.).
            </p>
            <Form
                v-bind="CourseController.import.form()"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-4"
            >
                <label
                    class="flex cursor-pointer flex-col items-center gap-2 rounded-lg border border-dashed border-input px-6 py-10 text-center text-sm text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                >
                    <UploadCloud class="size-8" />
                    <span>Válassz egy zip fájlt, vagy húzd ide</span>
                    <input
                        type="file"
                        name="archive"
                        accept=".zip,application/zip"
                        class="hidden"
                    />
                </label>
                <InputError :message="errors.archive" />
                <Button type="submit" :disabled="processing" class="self-start">
                    Importálás
                </Button>
            </Form>
        </Card>

        <Card class="max-w-xl p-6">
            <h2 class="mb-1 font-medium">MD fájlok import</h2>
            <p class="mb-4 text-sm text-muted-foreground">
                Zip helyett egyenként is feltölthetsz .md oldalfájlokat.
                Mindegyik fájlból egy új, saját leckét tartalmazó kurzus jön
                létre (egy oldallal leckénként), amit utólag az admin
                felületen kell majd kitölteni és rendszerezni.
            </p>
            <Form
                v-bind="CourseController.import.form()"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-4"
            >
                <label
                    class="flex cursor-pointer flex-col items-center gap-2 rounded-lg border border-dashed border-input px-6 py-10 text-center text-sm text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                >
                    <FileText class="size-8" />
                    <span>Válassz egy vagy több .md fájlt, vagy húzd ide</span>
                    <input
                        type="file"
                        name="files[]"
                        accept=".md"
                        multiple
                        class="hidden"
                    />
                </label>
                <InputError :message="errors.files" />
                <Button type="submit" :disabled="processing" class="self-start">
                    Importálás
                </Button>
            </Form>
        </Card>

        <Card class="max-w-xl p-6">
            <h2 class="mb-1 font-medium">course.json import</h2>
            <p class="mb-4 text-sm text-muted-foreground">
                Csak egy önálló course.json fájlt is feltölthetsz, tartalom
                (.md fájlok) nélkül. Ilyenkor a benne leírt kurzus/lecke/oldal
                váza üres tartalommal létrejön (vagy frissül, ha a slug már
                létezik), a page-eket pedig utólag az admin felületen kell
                megírni.
            </p>
            <Form
                v-bind="CourseController.import.form()"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-4"
            >
                <label
                    class="flex cursor-pointer flex-col items-center gap-2 rounded-lg border border-dashed border-input px-6 py-10 text-center text-sm text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                >
                    <Braces class="size-8" />
                    <span>Válassz egy course.json fájlt, vagy húzd ide</span>
                    <input
                        type="file"
                        name="manifest"
                        accept=".json,application/json"
                        class="hidden"
                    />
                </label>
                <InputError :message="errors.manifest" />
                <Button type="submit" :disabled="processing" class="self-start">
                    Importálás
                </Button>
            </Form>
        </Card>
    </div>
</template>
