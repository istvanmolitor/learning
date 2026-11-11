<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    FileText,
    GraduationCap,
    GripVertical,
    Plus,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import LessonController from '@/actions/App/Http/Controllers/Admin/LessonController';
import PageController from '@/actions/App/Http/Controllers/Admin/PageController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import CourseTabs from '@/components/admin/CourseTabs.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type PageItem = { id: number; title: string; slug: string; order: number };
type LessonItem = {
    id: number;
    title: string;
    slug: string;
    order: number;
    pages: PageItem[];
};

const props = defineProps<{
    course: { id: number; title: string; lessons: LessonItem[] };
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

const lessons = ref<LessonItem[]>(
    props.course.lessons.map((lesson) => ({
        ...lesson,
        pages: [...lesson.pages],
    })),
);

const draggedPage = ref<{ pageId: number; fromLessonId: number } | null>(null);
const dropTarget = ref<{ lessonId: number; index: number } | null>(null);

function onDragStart(lessonId: number, pageId: number) {
    draggedPage.value = { pageId, fromLessonId: lessonId };
}

function onDragEnd() {
    draggedPage.value = null;
    dropTarget.value = null;
}

function onRowDragOver(event: DragEvent, lessonId: number, index: number) {
    if (!draggedPage.value) return;
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    const after = event.clientY - rect.top > rect.height / 2;
    dropTarget.value = { lessonId, index: after ? index + 1 : index };
}

function onListDragOver(lessonId: number, endIndex: number) {
    if (!draggedPage.value) return;
    dropTarget.value = { lessonId, index: endIndex };
}

function onDrop() {
    if (!draggedPage.value || !dropTarget.value) return;

    const { pageId, fromLessonId } = draggedPage.value;
    const { lessonId: toLessonId, index: targetIndex } = dropTarget.value;

    const fromLesson = lessons.value.find((l) => l.id === fromLessonId);
    const toLesson = lessons.value.find((l) => l.id === toLessonId);

    if (fromLesson && toLesson) {
        const fromIndex = fromLesson.pages.findIndex((p) => p.id === pageId);

        if (fromIndex !== -1) {
            const [page] = fromLesson.pages.splice(fromIndex, 1);
            const insertAt =
                fromLesson === toLesson && fromIndex < targetIndex
                    ? targetIndex - 1
                    : targetIndex;

            toLesson.pages.splice(insertAt, 0, page);
            persistOrder();
        }
    }

    draggedPage.value = null;
    dropTarget.value = null;
}

function persistOrder() {
    lessons.value.forEach((lesson) => {
        lesson.pages.forEach((page, index) => {
            page.order = index;
        });
    });

    router.post(
        CourseController.reorderPages.url(props.course.id),
        {
            pages: lessons.value.flatMap((lesson) =>
                lesson.pages.map((page) => ({
                    id: page.id,
                    lesson_id: lesson.id,
                    order: page.order,
                })),
            ),
        },
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <Head :title="`Leckék – ${course.title}`" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <AdminHeader
            :icon="GraduationCap"
            title="Leckék"
            :description="course.title"
            :back-href="CourseController.index()"
            back-label="Kurzusok"
        />

        <CourseTabs
            :course-id="course.id"
            :can-manage-enrollments="canManageEnrollments"
        />

        <Card>
            <CardHeader class="flex items-center justify-between">
                <CardTitle>Leckék és oldalak</CardTitle>
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
            <CardContent class="flex flex-col gap-4">
                <p
                    v-if="lessons.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Még nincs lecke.
                </p>

                <div
                    v-for="(lesson, lessonIndex) in lessons"
                    :key="lesson.id"
                    class="rounded-lg border border-border"
                >
                    <div
                        class="flex items-center gap-3 border-b border-border bg-muted/30 p-3"
                    >
                        <span
                            class="flex size-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary"
                        >
                            {{ lessonIndex + 1 }}
                        </span>
                        <BookOpen
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="flex-1 text-sm font-medium">{{
                            lesson.title
                        }}</span>
                        <template v-if="canManageAllCourses">
                            <Link :href="PageController.create(lesson.id)">
                                <Button variant="ghost" size="sm">
                                    <Plus class="size-3.5" />
                                    Oldal
                                </Button>
                            </Link>
                            <Link :href="LessonController.edit(lesson.id)">
                                <Button variant="outline" size="sm"
                                    >Szerkesztés</Button
                                >
                            </Link>
                        </template>
                    </div>

                    <ul
                        :class="
                            cn(
                                'flex min-h-14 flex-col gap-1 p-2',
                                dropTarget?.lessonId === lesson.id &&
                                    dropTarget.index === lesson.pages.length &&
                                    'rounded-md bg-primary/5 outline-1 outline-primary/40 outline-dashed',
                            )
                        "
                        @dragover.self.prevent="
                            onListDragOver(lesson.id, lesson.pages.length)
                        "
                        @drop.prevent="onDrop"
                    >
                        <li
                            v-for="(pageItem, pageIndex) in lesson.pages"
                            :key="pageItem.id"
                            :draggable="canManageAllCourses"
                            :class="
                                cn(
                                    'flex items-center gap-3 rounded-md border border-transparent p-2 text-sm transition-colors hover:bg-muted/40',
                                    canManageAllCourses &&
                                        'cursor-grab active:cursor-grabbing',
                                    dropTarget?.lessonId === lesson.id &&
                                        dropTarget.index === pageIndex &&
                                        'border-t-primary',
                                )
                            "
                            @dragstart="onDragStart(lesson.id, pageItem.id)"
                            @dragend="onDragEnd"
                            @dragover.prevent="
                                onRowDragOver($event, lesson.id, pageIndex)
                            "
                        >
                            <GripVertical
                                v-if="canManageAllCourses"
                                class="size-3.5 shrink-0 text-muted-foreground"
                            />
                            <FileText
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                            <span class="flex-1">{{ pageItem.title }}</span>
                            <Link
                                v-if="canManageAllCourses"
                                :href="PageController.edit(pageItem.id)"
                            >
                                <Button variant="outline" size="sm"
                                    >Szerkesztés</Button
                                >
                            </Link>
                        </li>
                        <li
                            v-if="lesson.pages.length === 0"
                            class="rounded-md border border-dashed border-border p-3 text-center text-xs text-muted-foreground"
                            @dragover.prevent="onListDragOver(lesson.id, 0)"
                        >
                            Húzz ide oldalt egy másik leckéből, vagy adj hozzá
                            újat.
                        </li>
                    </ul>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
