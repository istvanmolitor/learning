<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { BookOpen, GraduationCap, Plus, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import CourseEnrollmentController from '@/actions/App/Http/Controllers/Admin/CourseEnrollmentController';
import LessonController from '@/actions/App/Http/Controllers/Admin/LessonController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const props = defineProps<{
    course: {
        id: number;
        title: string;
        slug: string;
        description: string | null;
        thumbnail: string | null;
        is_published: boolean;
        order: number;
        lessons: { id: number; title: string; order: number }[];
        creator: { id: number; name: string } | null;
        enrolledUsers: { id: number; name: string; email: string; pivot: { id: number } }[];
        availableUsers: { id: number; name: string; email: string }[];
    } | null;
    canManageEnrollments?: boolean;
}>();

const page = usePage();
const permissions = computed(() => page.props.auth.permissions);
const canManageAllCourses = computed(() =>
    permissions.value.includes('manage-courses'),
);

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
                            >{{ course?.description }}</textarea
                        >
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

        <Card v-if="course">
            <CardHeader class="flex items-center justify-between">
                <CardTitle>Leckék</CardTitle>
                <Link
                    v-if="canManageAllCourses"
                    :href="LessonController.create(course.id)"
                >
                    <Button variant="outline" size="sm">
                        <Plus class="size-3.5" />
                        Új lecke
                    </Button>
                </Link>
            </CardHeader>
            <CardContent>
                <ul
                    v-if="course.lessons.length > 0"
                    class="flex flex-col gap-2"
                >
                    <li
                        v-for="(lesson, index) in course.lessons"
                        :key="lesson.id"
                        class="flex items-center gap-3 rounded-lg border border-border p-3 text-sm transition-colors hover:bg-muted/40"
                    >
                        <span
                            class="flex size-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary"
                        >
                            {{ index + 1 }}
                        </span>
                        <BookOpen class="size-4 text-muted-foreground" />
                        <span class="flex-1">{{ lesson.title }}</span>
                        <Link
                            v-if="canManageAllCourses"
                            :href="LessonController.edit(lesson.id)"
                        >
                            <Button variant="outline" size="sm"
                                >Szerkesztés</Button
                            >
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    Még nincs lecke.
                </p>
            </CardContent>
        </Card>

        <Card v-if="course && canManageEnrollments">
            <CardHeader>
                <CardTitle>Hozzárendelt felhasználók</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        v-for="user in course.enrolledUsers"
                        :key="user.id"
                        variant="secondary"
                        class="gap-1.5 py-1.5 pr-1.5 pl-2.5"
                    >
                        {{ user.name }}
                        <Form
                            v-bind="
                                CourseEnrollmentController.destroy.form([
                                    course.id,
                                    user.pivot.id,
                                ])
                            "
                        >
                            <button
                                type="submit"
                                class="rounded-full p-0.5 text-muted-foreground hover:bg-background hover:text-destructive"
                            >
                                <X class="size-3" />
                            </button>
                        </Form>
                    </Badge>
                    <p
                        v-if="course.enrolledUsers.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Még nincs hozzárendelt felhasználó.
                    </p>
                </div>

                <Form
                    v-if="course.availableUsers.length > 0"
                    v-bind="CourseEnrollmentController.store.form(course.id)"
                    class="flex items-center gap-2"
                >
                    <Select
                        name="user_id"
                        :default-value="String(course.availableUsers[0]?.id)"
                    >
                        <SelectTrigger size="sm" class="w-64">
                            <SelectValue placeholder="Válassz felhasználót" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="user in course.availableUsers"
                                :key="user.id"
                                :value="String(user.id)"
                            >
                                {{ user.name }} ({{ user.email }})
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Button type="submit" size="sm" variant="outline"
                        >Hozzárendelés</Button
                    >
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
