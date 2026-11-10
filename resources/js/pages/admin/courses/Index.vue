<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import {
    BookOpenCheck,
    Download,
    GraduationCap,
    Layers,
    Pencil,
    Trash2,
    UploadCloud,
} from '@lucide/vue';
import { computed } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

const props = defineProps<{
    courses: {
        id: number;
        title: string;
        slug: string;
        thumbnail: string | null;
        is_published: boolean;
        lessons_count: number;
        created_by: number | null;
        creator: { id: number; name: string } | null;
    }[];
}>();

const page = usePage();
const permissions = computed(() => page.props.auth.permissions);
const canManageAllCourses = computed(() =>
    permissions.value.includes('manage-courses'),
);

const publishedCount = computed(
    () => props.courses.filter((course) => course.is_published).length,
);

const totalLessons = computed(() =>
    props.courses.reduce((sum, course) => sum + course.lessons_count, 0),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin – Kurzusok', href: CourseController.index() },
        ],
    },
});
</script>

<template>
    <Head title="Admin – Kurzusok" />

    <div class="flex flex-col gap-6 p-4">
        <AdminHeader
            :icon="GraduationCap"
            title="Kurzusok"
            description="Hozd létre és kezeld a platform kurzusait, leckéit és tartalmát."
        >
            <Form
                v-bind="CourseController.import.form()"
                v-slot="{ errors, processing }"
                class="flex items-center gap-2"
            >
                <label
                    class="flex h-9 cursor-pointer items-center gap-2 rounded-md border border-dashed border-input px-3 text-sm text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                >
                    <UploadCloud class="size-4" />
                    Importálás
                    <input
                        type="file"
                        name="archive"
                        accept=".zip,application/zip"
                        required
                        class="hidden"
                    />
                </label>
                <Button type="submit" variant="outline" :disabled="processing"
                    >Feltöltés</Button
                >
                <InputError :message="errors.archive" />
            </Form>
            <Link :href="CourseController.create()">
                <Button>Új kurzus</Button>
            </Link>
        </AdminHeader>

        <div class="grid gap-4 sm:grid-cols-3">
            <Card class="flex-row items-center gap-4 px-5 py-4">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <GraduationCap class="size-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold leading-none">
                        {{ courses.length }}
                    </p>
                    <p class="text-xs text-muted-foreground">Kurzus</p>
                </div>
            </Card>
            <Card class="flex-row items-center gap-4 px-5 py-4">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                >
                    <BookOpenCheck class="size-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold leading-none">
                        {{ publishedCount }}
                    </p>
                    <p class="text-xs text-muted-foreground">Publikus</p>
                </div>
            </Card>
            <Card class="flex-row items-center gap-4 px-5 py-4">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400"
                >
                    <Layers class="size-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold leading-none">
                        {{ totalLessons }}
                    </p>
                    <p class="text-xs text-muted-foreground">Lecke összesen</p>
                </div>
            </Card>
        </div>

        <Card class="p-0">
            <div v-if="courses.length === 0" class="flex flex-col items-center gap-2 py-14 text-center">
                <GraduationCap class="size-8 text-muted-foreground" />
                <p class="font-medium">Még nincs kurzus</p>
                <p class="text-sm text-muted-foreground">
                    Hozd létre az első kurzust, vagy importálj egyet zip
                    fájlból.
                </p>
            </div>

            <ul v-else class="divide-y divide-border">
                <li
                    v-for="course in courses"
                    :key="course.id"
                    class="flex flex-wrap items-center gap-4 p-4 transition-colors hover:bg-muted/40"
                >
                    <img
                        v-if="course.thumbnail"
                        :src="`/storage/${course.thumbnail}`"
                        :alt="course.title"
                        class="size-10 shrink-0 rounded-lg object-cover"
                    />
                    <div
                        v-else
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary"
                    >
                        {{ course.title.charAt(0).toUpperCase() }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ course.title }}</p>
                        <p class="truncate text-xs text-muted-foreground">
                            /{{ course.slug }}
                            <span v-if="course.creator">
                                · létrehozta: {{ course.creator.name }}</span
                            >
                        </p>
                    </div>

                    <Badge variant="outline" class="gap-1">
                        <Layers class="size-3" />
                        {{ course.lessons_count }} lecke
                    </Badge>

                    <Badge
                        :class="
                            course.is_published
                                ? 'border-transparent bg-emerald-500/15 text-emerald-700 dark:text-emerald-400'
                                : 'border-transparent bg-muted text-muted-foreground'
                        "
                    >
                        {{ course.is_published ? 'Publikus' : 'Vázlat' }}
                    </Badge>

                    <div class="ml-auto flex items-center gap-2">
                        <Link :href="CourseController.edit(course.id)">
                            <Button variant="outline" size="sm">
                                <Pencil class="size-3.5" />
                                Szerkesztés
                            </Button>
                        </Link>
                        <a
                            v-if="canManageAllCourses"
                            :href="CourseController.export(course.id).url"
                        >
                            <Button variant="ghost" size="sm">
                                <Download class="size-3.5" />
                                Export
                            </Button>
                        </a>
                        <Form
                            v-if="canManageAllCourses"
                            v-bind="
                                CourseController.destroy.form(course.id)
                            "
                        >
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
                    </div>
                </li>
            </ul>
        </Card>
    </div>
</template>
