<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    BookOpen,
    FileText,
    FileUp,
    GraduationCap,
    GripVertical,
    HelpCircle,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import LessonController from '@/actions/App/Http/Controllers/Admin/LessonController';
import PageController from '@/actions/App/Http/Controllers/Admin/PageController';
import QuestionController from '@/actions/App/Http/Controllers/Admin/QuestionController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import CourseTabs from '@/components/admin/CourseTabs.vue';
import MarkdownEditor from '@/components/admin/MarkdownEditor.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type PageItem = {
    id: number;
    title: string;
    slug: string;
    order: number;
    markdown: string;
    narration_text: string | null;
    narration_audio_path: string | null;
};
type LessonItem = {
    id: number;
    title: string;
    slug: string;
    order: number;
    questions: { id: number; text: string }[];
    pages: PageItem[];
};

const props = defineProps<{
    course: {
        id: number;
        title: string;
        slug: string;
        description: string | null;
        thumbnail: string | null;
        is_published: boolean;
        order: number;
        lessons: LessonItem[];
    };
    canManageEnrollments?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin – Kurzusok', href: CourseController.index() },
        ],
    },
});

const page = usePage();
const canManageAllCourses = computed(() =>
    page.props.auth.permissions.includes('manage-courses'),
);

/* ---------- tree state ---------- */

const lessons = ref<LessonItem[]>([]);

watch(
    () => props.course.lessons,
    (value) => {
        lessons.value = value.map((lesson) => ({
            ...lesson,
            pages: [...lesson.pages],
        }));
    },
    { immediate: true, deep: false },
);

/* ---------- selection (survives form-save remounts) ---------- */

type Selection =
    | { type: 'course' }
    | { type: 'lesson'; id: number }
    | { type: 'page'; id: number };

const storageKey = `course-editor-selection-${props.course.id}`;

function restoreSelection(): Selection {
    try {
        const raw = sessionStorage.getItem(storageKey);
        if (raw) return JSON.parse(raw) as Selection;
    } catch {
        /* ignore */
    }
    return { type: 'course' };
}

const selected = ref<Selection>(restoreSelection());

function select(selection: Selection) {
    selected.value = selection;
    sessionStorage.setItem(storageKey, JSON.stringify(selection));
}

const selectedLesson = computed(() => {
    if (selected.value.type === 'lesson') {
        return lessons.value.find((l) => l.id === selected.value.id) ?? null;
    }
    if (selected.value.type === 'page') {
        return (
            lessons.value.find((l) =>
                l.pages.some((p) => p.id === selected.value.id),
            ) ?? null
        );
    }
    return null;
});

const selectedPage = computed(() =>
    selected.value.type === 'page'
        ? (selectedLesson.value?.pages.find(
              (p) => p.id === selected.value.id,
          ) ?? null)
        : null,
);

// If the selected item disappeared (deleted, re-imported), fall back to course.
watch([lessons, selected], () => {
    if (selected.value.type !== 'course' && !selectedLesson.value) {
        select({ type: 'course' });
    }
});

/* ---------- completeness badges ---------- */

const courseIssues = computed(() => {
    const issues: string[] = [];
    if (!props.course.description) issues.push('nincs leírás');
    if (!props.course.thumbnail) issues.push('nincs borítókép');
    if (!props.course.is_published) issues.push('nem publikált');
    return issues;
});

function lessonIssues(lesson: LessonItem): string[] {
    const issues: string[] = [];
    if (lesson.pages.length === 0) issues.push('nincs oldal');
    if (lesson.questions.length === 0) issues.push('nincs kérdés');
    if (lesson.pages.some((p) => !p.markdown.trim()))
        issues.push('üres oldal');
    return issues;
}

/* ---------- inline rename ---------- */

const renaming = ref<{ type: 'lesson' | 'page'; id: number } | null>(null);
const renameValue = ref('');

function startRename(type: 'lesson' | 'page', id: number, current: string) {
    if (!canManageAllCourses.value) return;
    renaming.value = { type, id };
    renameValue.value = current;
}

function commitRename() {
    if (!renaming.value) return;
    const { type, id } = renaming.value;
    const title = renameValue.value.trim();
    renaming.value = null;

    if (!title) return;

    const url =
        type === 'lesson'
            ? LessonController.update.url(id)
            : PageController.update.url(id);

    router.patch(
        url,
        { title },
        { preserveScroll: true, preserveState: true },
    );
}

/* ---------- create / delete ---------- */

function addLesson() {
    router.post(
        LessonController.store.url(props.course.id),
        { title: `Új lecke` },
        { preserveScroll: true, preserveState: true },
    );
}

function addPage(lessonId: number) {
    router.post(
        PageController.store.url(lessonId),
        { title: 'Új oldal' },
        { preserveScroll: true, preserveState: true },
    );
}

function deleteLesson(lesson: LessonItem) {
    if (
        !window.confirm(
            `Biztosan törlöd a(z) „${lesson.title}" leckét minden oldalával együtt?`,
        )
    ) {
        return;
    }

    select({ type: 'course' });
    router.delete(LessonController.destroy.url(lesson.id), {
        preserveScroll: true,
        preserveState: true,
    });
}

function deletePage(pageItem: PageItem) {
    if (!window.confirm(`Biztosan törlöd a(z) „${pageItem.title}" oldalt?`)) {
        return;
    }

    const lesson = selectedLesson.value;
    select(lesson ? { type: 'lesson', id: lesson.id } : { type: 'course' });
    router.delete(PageController.destroy.url(pageItem.id), {
        preserveScroll: true,
        preserveState: true,
    });
}

/* ---------- markdown file upload to a lesson ---------- */

function uploadMarkdown(lessonId: number, event: Event) {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    if (!files.length) return;

    router.post(
        LessonController.uploadPages.url(lessonId),
        { files },
        { preserveScroll: true, preserveState: true, forceFormData: true },
    );

    input.value = '';
}

/* ---------- drag & drop: pages ---------- */

const draggedPage = ref<{ pageId: number; fromLessonId: number } | null>(null);
const dropTarget = ref<{ lessonId: number; index: number } | null>(null);

function onPageDragStart(lessonId: number, pageId: number) {
    draggedPage.value = { pageId, fromLessonId: lessonId };
}

function onPageDragEnd() {
    draggedPage.value = null;
    dropTarget.value = null;
}

function onPageRowDragOver(event: DragEvent, lessonId: number, index: number) {
    if (!draggedPage.value) return;
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    const after = event.clientY - rect.top > rect.height / 2;
    dropTarget.value = { lessonId, index: after ? index + 1 : index };
}

function onPageListDragOver(lessonId: number, endIndex: number) {
    if (!draggedPage.value) return;
    dropTarget.value = { lessonId, index: endIndex };
}

function onPageDrop() {
    if (!draggedPage.value || !dropTarget.value) return;

    const { pageId, fromLessonId } = draggedPage.value;
    const { lessonId: toLessonId, index: targetIndex } = dropTarget.value;

    const fromLesson = lessons.value.find((l) => l.id === fromLessonId);
    const toLesson = lessons.value.find((l) => l.id === toLessonId);

    if (fromLesson && toLesson) {
        const fromIndex = fromLesson.pages.findIndex((p) => p.id === pageId);

        if (fromIndex !== -1) {
            const [moved] = fromLesson.pages.splice(fromIndex, 1);
            const insertAt =
                fromLesson === toLesson && fromIndex < targetIndex
                    ? targetIndex - 1
                    : targetIndex;

            toLesson.pages.splice(insertAt, 0, moved);
            persistPageOrder();
        }
    }

    draggedPage.value = null;
    dropTarget.value = null;
}

function persistPageOrder() {
    lessons.value.forEach((lesson) => {
        lesson.pages.forEach((pageItem, index) => {
            pageItem.order = index;
        });
    });

    router.post(
        CourseController.reorderPages.url(props.course.id),
        {
            pages: lessons.value.flatMap((lesson) =>
                lesson.pages.map((pageItem) => ({
                    id: pageItem.id,
                    lesson_id: lesson.id,
                    order: pageItem.order,
                })),
            ),
        },
        { preserveScroll: true, preserveState: true },
    );
}

/* ---------- drag & drop: lessons ---------- */

const draggedLessonId = ref<number | null>(null);
const lessonDropIndex = ref<number | null>(null);

function onLessonDragOver(event: DragEvent, index: number) {
    if (draggedLessonId.value === null) return;
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    const after = event.clientY - rect.top > rect.height / 2;
    lessonDropIndex.value = after ? index + 1 : index;
}

function onLessonDrop() {
    if (draggedLessonId.value === null || lessonDropIndex.value === null) {
        return;
    }

    const fromIndex = lessons.value.findIndex(
        (l) => l.id === draggedLessonId.value,
    );

    if (fromIndex !== -1) {
        const [moved] = lessons.value.splice(fromIndex, 1);
        const insertAt =
            fromIndex < lessonDropIndex.value
                ? lessonDropIndex.value - 1
                : lessonDropIndex.value;
        lessons.value.splice(insertAt, 0, moved);

        router.post(
            CourseController.reorderLessons.url(props.course.id),
            {
                lessons: lessons.value.map((lesson, index) => ({
                    id: lesson.id,
                    order: index,
                })),
            },
            { preserveScroll: true, preserveState: true },
        );
    }

    draggedLessonId.value = null;
    lessonDropIndex.value = null;
}

/* ---------- course meta form helpers ---------- */

const thumbnailPreview = ref<string | null>(
    props.course.thumbnail ? `/storage/${props.course.thumbnail}` : null,
);

function onThumbnailChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    thumbnailPreview.value = file
        ? URL.createObjectURL(file)
        : props.course.thumbnail
          ? `/storage/${props.course.thumbnail}`
          : null;
}

const formOptions = { preserveScroll: true, preserveState: true };
</script>

<template>
    <Head :title="`Szerkesztő – ${course.title}`" />

    <div class="flex flex-col gap-6 p-4">
        <AdminHeader
            :icon="GraduationCap"
            title="Kurzus-szerkesztő"
            :description="course.title"
            :back-href="CourseController.index()"
            back-label="Kurzusok"
        />

        <CourseTabs
            :course-id="course.id"
            :can-manage-enrollments="canManageEnrollments"
        />

        <div class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
            <!-- ============ left: tree ============ -->
            <div class="flex flex-col gap-2">
                <button
                    type="button"
                    :class="
                        cn(
                            'flex items-center gap-2 rounded-md border p-3 text-left text-sm font-medium transition-colors hover:bg-muted/40',
                            selected.type === 'course' &&
                                'border-primary bg-primary/5',
                        )
                    "
                    @click="select({ type: 'course' })"
                >
                    <GraduationCap class="size-4 shrink-0 text-primary" />
                    <span class="flex-1 truncate">{{ course.title }}</span>
                    <Badge
                        v-if="courseIssues.length"
                        variant="outline"
                        class="border-amber-500/60 text-amber-600"
                    >
                        {{ courseIssues.length }}
                    </Badge>
                </button>

                <div
                    v-for="(lesson, lessonIndex) in lessons"
                    :key="lesson.id"
                    class="rounded-lg border border-border"
                    @dragover.prevent="onLessonDragOver($event, lessonIndex)"
                    @drop.prevent="onLessonDrop"
                >
                    <div
                        :class="
                            cn(
                                'flex items-center gap-2 rounded-t-lg border-b border-border bg-muted/30 p-2 text-sm',
                                selected.type === 'lesson' &&
                                    selected.id === lesson.id &&
                                    'bg-primary/5',
                                lessonDropIndex === lessonIndex &&
                                    draggedLessonId !== null &&
                                    'border-t-2 border-t-primary',
                            )
                        "
                        :draggable="canManageAllCourses && renaming === null"
                        @dragstart="draggedLessonId = lesson.id"
                        @dragend="
                            draggedLessonId = null;
                            lessonDropIndex = null;
                        "
                    >
                        <GripVertical
                            v-if="canManageAllCourses"
                            class="size-3.5 shrink-0 cursor-grab text-muted-foreground"
                        />
                        <BookOpen class="size-4 shrink-0 text-muted-foreground" />

                        <input
                            v-if="
                                renaming?.type === 'lesson' &&
                                renaming.id === lesson.id
                            "
                            v-model="renameValue"
                            class="w-full rounded border border-input bg-background px-1 py-0.5 text-sm"
                            autofocus
                            @keydown.enter.prevent="commitRename"
                            @keydown.esc="renaming = null"
                            @blur="commitRename"
                        />
                        <button
                            v-else
                            type="button"
                            class="flex-1 truncate text-left font-medium"
                            @click="select({ type: 'lesson', id: lesson.id })"
                            @dblclick="
                                startRename('lesson', lesson.id, lesson.title)
                            "
                        >
                            {{ lessonIndex + 1 }}. {{ lesson.title }}
                        </button>

                        <Badge
                            v-if="lessonIssues(lesson).length"
                            variant="outline"
                            class="shrink-0 border-amber-500/60 text-amber-600"
                            :title="lessonIssues(lesson).join(', ')"
                        >
                            {{ lessonIssues(lesson).length }}
                        </Badge>
                    </div>

                    <ul
                        :class="
                            cn(
                                'flex min-h-10 flex-col gap-0.5 p-1.5',
                                dropTarget?.lessonId === lesson.id &&
                                    dropTarget.index === lesson.pages.length &&
                                    'rounded-md bg-primary/5 outline-1 outline-primary/40 outline-dashed',
                            )
                        "
                        @dragover.self.prevent="
                            onPageListDragOver(lesson.id, lesson.pages.length)
                        "
                        @drop.prevent="onPageDrop"
                    >
                        <li
                            v-for="(pageItem, pageIndex) in lesson.pages"
                            :key="pageItem.id"
                            :draggable="
                                canManageAllCourses && renaming === null
                            "
                            :class="
                                cn(
                                    'flex items-center gap-2 rounded-md border border-transparent p-1.5 text-sm transition-colors hover:bg-muted/40',
                                    canManageAllCourses &&
                                        'cursor-grab active:cursor-grabbing',
                                    selected.type === 'page' &&
                                        selected.id === pageItem.id &&
                                        'border-primary bg-primary/5',
                                    dropTarget?.lessonId === lesson.id &&
                                        dropTarget.index === pageIndex &&
                                        'border-t-primary',
                                )
                            "
                            @dragstart="
                                onPageDragStart(lesson.id, pageItem.id)
                            "
                            @dragend="onPageDragEnd"
                            @dragover.prevent="
                                onPageRowDragOver($event, lesson.id, pageIndex)
                            "
                        >
                            <FileText
                                class="size-3.5 shrink-0 text-muted-foreground"
                            />

                            <input
                                v-if="
                                    renaming?.type === 'page' &&
                                    renaming.id === pageItem.id
                                "
                                v-model="renameValue"
                                class="w-full rounded border border-input bg-background px-1 py-0.5 text-sm"
                                autofocus
                                @keydown.enter.prevent="commitRename"
                                @keydown.esc="renaming = null"
                                @blur="commitRename"
                            />
                            <button
                                v-else
                                type="button"
                                class="flex-1 truncate text-left"
                                @click="
                                    select({ type: 'page', id: pageItem.id })
                                "
                                @dblclick="
                                    startRename(
                                        'page',
                                        pageItem.id,
                                        pageItem.title,
                                    )
                                "
                            >
                                {{ pageItem.title }}
                            </button>

                            <AlertCircle
                                v-if="!pageItem.markdown.trim()"
                                class="size-3.5 shrink-0 text-amber-500"
                                title="Üres oldal"
                            />
                        </li>

                        <li
                            v-if="canManageAllCourses"
                            class="flex items-center gap-1"
                        >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-7 flex-1 justify-start text-xs text-muted-foreground"
                                @click="addPage(lesson.id)"
                            >
                                <Plus class="size-3" /> Új oldal
                            </Button>
                            <label
                                class="flex h-7 cursor-pointer items-center gap-1 rounded-md px-2 text-xs text-muted-foreground hover:bg-muted/60"
                                title=".md fájlok hozzáadása oldalakként"
                            >
                                <FileUp class="size-3" /> .md
                                <input
                                    type="file"
                                    accept=".md"
                                    multiple
                                    class="hidden"
                                    @change="uploadMarkdown(lesson.id, $event)"
                                />
                            </label>
                        </li>
                    </ul>
                </div>

                <Button
                    v-if="canManageAllCourses"
                    variant="outline"
                    class="justify-start"
                    @click="addLesson"
                >
                    <Plus class="size-4" /> Új lecke
                </Button>
            </div>

            <!-- ============ right: detail panel ============ -->
            <div class="min-w-0">
                <!-- course meta -->
                <Card v-if="selected.type === 'course'">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            Kurzus alapadatai
                            <Badge
                                v-for="issue in courseIssues"
                                :key="issue"
                                variant="outline"
                                class="border-amber-500/60 text-amber-600"
                            >
                                {{ issue }}
                            </Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            v-bind="CourseController.update.form(course.id)"
                            :options="formOptions"
                            class="flex flex-col gap-5"
                            v-slot="{ errors, processing }"
                        >
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="course-title">Cím</Label>
                                    <Input
                                        id="course-title"
                                        name="title"
                                        :default-value="course.title"
                                        required
                                    />
                                    <InputError :message="errors.title" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="course-slug">Slug</Label>
                                    <Input
                                        id="course-slug"
                                        name="slug"
                                        :default-value="course.slug"
                                        required
                                    />
                                    <InputError :message="errors.slug" />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="course-description">Leírás</Label>
                                <textarea
                                    id="course-description"
                                    name="description"
                                    rows="4"
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                    >{{ course.description }}</textarea
                                >
                                <InputError :message="errors.description" />
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="course-thumbnail"
                                        >Borítókép</Label
                                    >
                                    <img
                                        v-if="thumbnailPreview"
                                        :src="thumbnailPreview"
                                        alt=""
                                        class="h-32 w-auto rounded-md border object-cover"
                                    />
                                    <input
                                        id="course-thumbnail"
                                        type="file"
                                        name="thumbnail"
                                        accept="image/*"
                                        class="text-sm"
                                        @change="onThumbnailChange"
                                    />
                                    <InputError :message="errors.thumbnail" />
                                </div>

                                <div class="flex flex-col gap-4">
                                    <div class="grid gap-2 sm:w-32">
                                        <Label for="course-order"
                                            >Sorrend</Label
                                        >
                                        <Input
                                            id="course-order"
                                            type="number"
                                            name="order"
                                            :default-value="course.order"
                                        />
                                    </div>
                                    <label
                                        class="flex items-center gap-2 text-sm"
                                    >
                                        <Checkbox
                                            name="is_published"
                                            :default-value="
                                                course.is_published
                                            "
                                            value="1"
                                        />
                                        Publikált
                                    </label>
                                </div>
                            </div>

                            <Button
                                type="submit"
                                :disabled="processing"
                                class="w-fit"
                                >Mentés</Button
                            >
                        </Form>
                    </CardContent>
                </Card>

                <!-- lesson panel -->
                <template
                    v-else-if="selected.type === 'lesson' && selectedLesson"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <BookOpen class="size-5" /> Lecke:
                                {{ selectedLesson.title }}
                                <Badge
                                    v-for="issue in lessonIssues(
                                        selectedLesson,
                                    )"
                                    :key="issue"
                                    variant="outline"
                                    class="border-amber-500/60 text-amber-600"
                                >
                                    {{ issue }}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-6">
                            <Form
                                v-bind="
                                    LessonController.update.form(
                                        selectedLesson.id,
                                    )
                                "
                                :options="formOptions"
                                class="flex flex-wrap items-end gap-4"
                                v-slot="{ errors, processing }"
                            >
                                <div class="grid min-w-60 gap-2">
                                    <Label for="lesson-title">Cím</Label>
                                    <Input
                                        id="lesson-title"
                                        name="title"
                                        :default-value="selectedLesson.title"
                                        required
                                    />
                                    <InputError :message="errors.title" />
                                </div>
                                <div class="grid min-w-60 gap-2">
                                    <Label for="lesson-slug">Slug</Label>
                                    <Input
                                        id="lesson-slug"
                                        name="slug"
                                        :default-value="selectedLesson.slug"
                                    />
                                    <InputError :message="errors.slug" />
                                </div>
                                <Button type="submit" :disabled="processing"
                                    >Mentés</Button
                                >
                            </Form>

                            <div class="flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <h3
                                        class="flex items-center gap-1.5 text-sm font-semibold"
                                    >
                                        <HelpCircle class="size-4" />
                                        Teszt kérdések ({{
                                            selectedLesson.questions.length
                                        }})
                                    </h3>
                                    <Link
                                        :href="
                                            QuestionController.create(
                                                selectedLesson.id,
                                            )
                                        "
                                    >
                                        <Button variant="outline" size="sm">
                                            <Plus class="size-3.5" /> Új kérdés
                                        </Button>
                                    </Link>
                                </div>
                                <ul class="flex flex-col gap-1">
                                    <li
                                        v-for="question in selectedLesson.questions"
                                        :key="question.id"
                                        class="flex items-center gap-2 rounded-md border p-2 text-sm"
                                    >
                                        <span class="flex-1 truncate">{{
                                            question.text
                                        }}</span>
                                        <Link
                                            :href="
                                                QuestionController.edit(
                                                    question.id,
                                                )
                                            "
                                        >
                                            <Button variant="ghost" size="sm">
                                                <Pencil class="size-3.5" />
                                            </Button>
                                        </Link>
                                    </li>
                                    <li
                                        v-if="
                                            selectedLesson.questions.length ===
                                            0
                                        "
                                        class="rounded-md border border-dashed p-3 text-center text-xs text-muted-foreground"
                                    >
                                        Ehhez a leckéhez még nincs kérdés — a
                                        lecke végén a tanuló e kérdésekkel
                                        ellenőrzi magát.
                                    </li>
                                </ul>
                            </div>

                            <div class="border-t pt-4">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    @click="deleteLesson(selectedLesson)"
                                >
                                    <Trash2 class="size-3.5" /> Lecke törlése
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </template>

                <!-- page panel -->
                <template v-else-if="selectedPage && selectedLesson">
                    <Form
                        v-bind="PageController.update.form(selectedPage.id)"
                        :options="formOptions"
                        class="flex flex-col gap-4"
                        v-slot="{ errors, processing }"
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <FileText class="size-5" />
                                    {{ selectedLesson.title }} /
                                    {{ selectedPage.title }}
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="flex flex-col gap-5">
                                <div class="flex flex-wrap gap-4">
                                    <div class="grid min-w-60 flex-1 gap-2">
                                        <Label for="page-title">Cím</Label>
                                        <Input
                                            id="page-title"
                                            name="title"
                                            :default-value="selectedPage.title"
                                            required
                                        />
                                        <InputError :message="errors.title" />
                                    </div>
                                    <div class="grid min-w-60 flex-1 gap-2">
                                        <Label for="page-slug">Slug</Label>
                                        <Input
                                            id="page-slug"
                                            name="slug"
                                            :default-value="selectedPage.slug"
                                        />
                                        <InputError :message="errors.slug" />
                                    </div>
                                </div>

                                <div class="grid gap-2">
                                    <Label>Tartalom (Markdown)</Label>
                                    <MarkdownEditor
                                        :key="selectedPage.id"
                                        name="markdown"
                                        :model-value="selectedPage.markdown"
                                        :rows="20"
                                    />
                                    <InputError :message="errors.markdown" />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base"
                                    >Narráció</CardTitle
                                >
                            </CardHeader>
                            <CardContent class="flex flex-col gap-4">
                                <div class="grid gap-2">
                                    <Label for="page-narration"
                                        >Felolvasandó szöveg (TTS)</Label
                                    >
                                    <textarea
                                        id="page-narration"
                                        name="narration_text"
                                        rows="4"
                                        class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                        >{{
                                            selectedPage.narration_text
                                        }}</textarea
                                    >
                                </div>

                                <audio
                                    v-if="selectedPage.narration_audio_path"
                                    controls
                                    class="w-full"
                                    :src="`/storage/${selectedPage.narration_audio_path}`"
                                />
                                <div class="grid gap-2">
                                    <Label for="page-audio"
                                        >Felolvasás mp3
                                        {{
                                            selectedPage.narration_audio_path
                                                ? '(üresen hagyva marad a jelenlegi)'
                                                : ''
                                        }}</Label
                                    >
                                    <input
                                        id="page-audio"
                                        type="file"
                                        name="narration_audio"
                                        accept="audio/mpeg,.mp3"
                                        class="text-sm"
                                    />
                                    <InputError
                                        :message="errors.narration_audio"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <div class="flex items-center justify-between">
                            <Button type="submit" :disabled="processing"
                                >Oldal mentése</Button
                            >
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                @click="deletePage(selectedPage)"
                            >
                                <Trash2 class="size-3.5" /> Oldal törlése
                            </Button>
                        </div>
                    </Form>
                </template>
            </div>
        </div>
    </div>
</template>
