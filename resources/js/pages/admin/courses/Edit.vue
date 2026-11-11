<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { GraduationCap } from '@lucide/vue';
import { ref } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import CourseTabs from '@/components/admin/CourseTabs.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    course: {
        id: number;
        title: string;
        slug: string;
        description: string | null;
        thumbnail: string | null;
        is_published: boolean;
        order: number;
        creator: { id: number; name: string } | null;
    } | null;
    canManageEnrollments?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin – Kurzusok', href: CourseController.index() },
        ],
    },
});

const thumbnailPreview = ref<string | null>(
    props.course?.thumbnail ? `/storage/${props.course.thumbnail}` : null,
);
const removeThumbnail = ref(false);

function onThumbnailChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    thumbnailPreview.value = file ? URL.createObjectURL(file) : null;
    removeThumbnail.value = false;
}
</script>

<template>
    <Head
        :title="course ? `Kurzus szerkesztése – ${course.title}` : 'Új kurzus'"
    />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <AdminHeader
            :icon="GraduationCap"
            :title="course ? 'Kurzus szerkesztése' : 'Új kurzus'"
            :description="
                course
                    ? course.title +
                      (course.creator
                          ? ` · létrehozta: ${course.creator.name}`
                          : '')
                    : 'Töltsd ki az alapadatokat az új kurzushoz.'
            "
            :back-href="CourseController.index()"
            back-label="Kurzusok"
        />

        <CourseTabs
            v-if="course"
            :course-id="course.id"
            :can-manage-enrollments="canManageEnrollments"
        />

        <Card>
            <CardHeader>
                <CardTitle>Alapadatok</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="
                        course
                            ? CourseController.update.form(course.id)
                            : CourseController.store.form()
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
                                :default-value="course?.title"
                                required
                            />
                            <InputError :message="errors.title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="slug">Slug</Label>
                            <Input
                                id="slug"
                                name="slug"
                                :default-value="course?.slug"
                                required
                            />
                            <InputError :message="errors.slug" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Leírás</Label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            >{{ course?.description }}</textarea>
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="thumbnail">Borítókép</Label>
                        <div class="flex items-center gap-4">
                            <img
                                v-if="thumbnailPreview && !removeThumbnail"
                                :src="thumbnailPreview"
                                alt=""
                                class="size-20 shrink-0 rounded-lg border border-input object-cover"
                            />
                            <Input
                                id="thumbnail"
                                type="file"
                                name="thumbnail"
                                accept="image/*"
                                class="max-w-xs"
                                @change="onThumbnailChange"
                            />
                        </div>
                        <InputError :message="errors.thumbnail" />
                        <label
                            v-if="course?.thumbnail"
                            class="flex w-fit cursor-pointer items-center gap-2 text-sm text-muted-foreground"
                        >
                            <input
                                type="hidden"
                                name="remove_thumbnail"
                                value="0"
                            />
                            <Checkbox
                                name="remove_thumbnail"
                                value="1"
                                v-model="removeThumbnail"
                            />
                            Borítókép eltávolítása
                        </label>
                    </div>

                    <div class="grid gap-2 sm:w-40">
                        <Label for="order">Sorrend</Label>
                        <Input
                            id="order"
                            type="number"
                            name="order"
                            :default-value="course?.order ?? 0"
                        />
                        <InputError :message="errors.order" />
                    </div>

                    <label
                        for="is_published"
                        class="flex cursor-pointer items-start gap-3 rounded-lg border border-input p-4 transition-colors hover:border-primary/50"
                    >
                        <input type="hidden" name="is_published" value="0" />
                        <Checkbox
                            id="is_published"
                            name="is_published"
                            value="1"
                            :default-value="course?.is_published ?? false"
                            class="mt-0.5"
                        />
                        <span>
                            <span class="block text-sm font-medium"
                                >Publikus</span
                            >
                            <span class="block text-xs text-muted-foreground"
                                >A kurzus megjelenik a tanulók számára a
                                felületen.</span
                            >
                        </span>
                    </label>

                    <Button type="submit" :disabled="processing" class="w-fit"
                        >Mentés</Button
                    >
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
