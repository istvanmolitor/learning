<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';
import { Form, Link, router } from '@inertiajs/vue3';
import { CheckCircle2, ChevronLeft, ChevronRight } from '@lucide/vue';
import LessonController from '@/actions/App/Http/Controllers/LessonController';
import PageController from '@/actions/App/Http/Controllers/PageController';
import { cn } from '@/lib/utils';

const props = defineProps<{
    course: { slug: string; title: string };
    lesson: { slug: string; title: string };
    page: { slug: string; title: string; is_completed: boolean };
    pagination: {
        previous: { slug: string; title: string } | null;
        next: { slug: string; title: string } | null;
        dots: { slug: string; is_completed: boolean }[];
    };
}>();

function goTo(slug: string) {
    router.visit(
        PageController.show.url([props.course.slug, props.lesson.slug, slug]),
    );
}

function onKeydown(event: KeyboardEvent) {
    if (event.target instanceof HTMLElement && event.target.closest('input, textarea')) {
        return;
    }
    if (event.key === 'ArrowLeft' && props.pagination.previous) {
        goTo(props.pagination.previous.slug);
    }
    if (event.key === 'ArrowRight') {
        if (props.pagination.next) {
            goTo(props.pagination.next.slug);
        } else if (props.page.is_completed) {
            router.visit(
                LessonController.questions.url([
                    props.course.slug,
                    props.lesson.slug,
                ]),
            );
        }
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <nav class="flex flex-col gap-3 border-t pt-4">
        <div class="flex items-center justify-center gap-1.5">
            <Link
                v-for="dot in pagination.dots"
                :key="dot.slug"
                :href="
                    PageController.show.url([course.slug, lesson.slug, dot.slug])
                "
                :class="
                    cn(
                        'size-2.5 rounded-full transition-all',
                        dot.slug === page.slug
                            ? 'w-6 bg-primary'
                            : dot.is_completed
                              ? 'bg-emerald-500'
                              : 'bg-muted-foreground/30 hover:bg-muted-foreground/50',
                    )
                "
                :aria-label="dot.slug"
            />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <Link
                v-if="pagination.previous"
                :href="
                    PageController.show.url([
                        course.slug,
                        lesson.slug,
                        pagination.previous.slug,
                    ])
                "
                class="flex flex-col justify-center rounded-lg border p-3 transition-colors hover:bg-muted/40"
            >
                <span
                    class="flex items-center gap-1 text-xs text-muted-foreground"
                >
                    <ChevronLeft class="size-3.5" /> Előző oldal
                </span>
                <span class="truncate font-medium">{{
                    pagination.previous.title
                }}</span>
            </Link>
            <span v-else />

            <Link
                v-if="page.is_completed"
                :href="
                    pagination.next
                        ? PageController.show.url([
                              course.slug,
                              lesson.slug,
                              pagination.next.slug,
                          ])
                        : LessonController.questions([course.slug, lesson.slug])
                "
                class="flex flex-col items-end justify-center rounded-lg bg-primary p-3 text-right text-primary-foreground shadow-sm transition-transform hover:scale-[1.01]"
            >
                <span class="flex items-center gap-1 text-xs opacity-85">
                    <CheckCircle2 class="size-3.5" /> Elvégezve
                </span>
                <span class="flex items-center gap-1 truncate font-medium">
                    {{
                        pagination.next
                            ? pagination.next.title
                            : 'Teszt kérdések'
                    }}
                    <ChevronRight class="size-4" />
                </span>
            </Link>
            <Form
                v-else
                v-bind="
                    PageController.complete.form([
                        course.slug,
                        lesson.slug,
                        page.slug,
                    ])
                "
                v-slot="{ processing }"
            >
                <button
                    type="submit"
                    :disabled="processing"
                    class="flex w-full flex-col items-end justify-center rounded-lg bg-primary p-3 text-right text-primary-foreground shadow-sm transition-transform hover:scale-[1.01] disabled:opacity-60"
                >
                    <span class="text-xs opacity-85">Elvégezve és tovább</span>
                    <span class="flex items-center gap-1 truncate font-medium">
                        {{
                            pagination.next
                                ? pagination.next.title
                                : 'Teszt kérdések'
                        }}
                        <ChevronRight class="size-4" />
                    </span>
                </button>
            </Form>
        </div>
    </nav>
</template>
