<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { CheckCircle2, Circle, HelpCircle, Plus, X } from '@lucide/vue';
import { ref } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import LessonController from '@/actions/App/Http/Controllers/Admin/LessonController';
import QuestionController from '@/actions/App/Http/Controllers/Admin/QuestionController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    lesson: { id: number; title: string };
    question: {
        id: number;
        text: string;
        order: number;
        answer_options: { id: number; text: string; is_correct: boolean }[];
    } | null;
    responses: {
        id: number;
        user: { name: string };
        answer_option: { text: string; is_correct: boolean };
    }[];
}>();

const options = ref<{ id: number | null; text: string }[]>(
    props.question?.answer_options.map((option) => ({
        id: option.id,
        text: option.text,
    })) ?? [
        { id: null, text: '' },
        { id: null, text: '' },
    ],
);

const correctIndex = ref(
    props.question?.answer_options.findIndex((option) => option.is_correct) ?? 0,
);

function addOption() {
    options.value.push({ id: null, text: '' });
}

function removeOption(index: number) {
    options.value.splice(index, 1);
    if (correctIndex.value >= options.value.length) {
        correctIndex.value = 0;
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin – Kurzusok', href: CourseController.index() },
        ],
    },
});
</script>

<template>
    <Head :title="question ? 'Kérdés szerkesztése' : 'Új kérdés'" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <AdminHeader
            :icon="HelpCircle"
            :title="question ? 'Kérdés szerkesztése' : 'Új kérdés'"
            :back-href="LessonController.edit(lesson.id)"
            :back-label="lesson.title"
        />

        <Card>
            <CardHeader>
                <CardTitle>Kérdés</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="
                        question
                            ? QuestionController.update.form(question.id)
                            : QuestionController.store.form(lesson.id)
                    "
                    class="flex flex-col gap-5"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="text">Kérdés szövege</Label>
                        <textarea
                            id="text"
                            name="text"
                            rows="2"
                            required
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            >{{ question?.text }}</textarea
                        >
                        <InputError :message="errors.text" />
                    </div>

                    <div class="grid gap-2 sm:w-40">
                        <Label for="order">Sorrend</Label>
                        <Input
                            id="order"
                            type="number"
                            name="order"
                            :default-value="question?.order ?? 0"
                        />
                    </div>

                    <div class="flex flex-col gap-3">
                        <Label>Válaszlehetőségek (jelöld a helyeset)</Label>

                        <label
                            v-for="(option, index) in options"
                            :key="index"
                            class="flex items-center gap-3 rounded-lg border p-3 transition-colors"
                            :class="
                                index === correctIndex
                                    ? 'border-primary bg-primary/5'
                                    : 'border-border hover:bg-muted/40'
                            "
                        >
                            <input
                                type="radio"
                                name="correct_option"
                                :value="index"
                                :checked="index === correctIndex"
                                @change="correctIndex = index"
                                class="sr-only"
                            />
                            <component
                                :is="
                                    index === correctIndex
                                        ? CheckCircle2
                                        : Circle
                                "
                                class="size-5 shrink-0"
                                :class="
                                    index === correctIndex
                                        ? 'text-primary'
                                        : 'text-muted-foreground'
                                "
                            />
                            <input
                                v-if="option.id"
                                type="hidden"
                                :name="`options[${index}][id]`"
                                :value="option.id"
                            />
                            <Input
                                :name="`options[${index}][text]`"
                                v-model="option.text"
                                class="flex-1 border-none bg-transparent shadow-none focus-visible:ring-0"
                                required
                                placeholder="Válasz szövege"
                            />
                            <Button
                                v-if="options.length > 2"
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click.prevent="removeOption(index)"
                            >
                                <X class="size-3.5" />
                            </Button>
                        </label>

                        <InputError :message="errors.options" />
                        <InputError :message="errors.correct_option" />

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="w-fit"
                            @click="addOption"
                        >
                            <Plus class="size-3.5" />
                            Opció hozzáadása
                        </Button>
                    </div>

                    <Button type="submit" :disabled="processing" class="w-fit"
                        >Mentés</Button
                    >
                </Form>
            </CardContent>
        </Card>

        <Card v-if="question && responses.length > 0">
            <CardHeader>
                <CardTitle>Beérkezett válaszok</CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <ul class="divide-y divide-border">
                    <li
                        v-for="response in responses"
                        :key="response.id"
                        class="flex items-center gap-3 px-6 py-3 text-sm"
                    >
                        <span class="flex-1 font-medium">{{
                            response.user.name
                        }}</span>
                        <span class="text-muted-foreground">{{
                            response.answer_option.text
                        }}</span>
                        <Badge
                            :class="
                                response.answer_option.is_correct
                                    ? 'border-transparent bg-emerald-500/15 text-emerald-700 dark:text-emerald-400'
                                    : 'border-transparent bg-destructive/15 text-destructive'
                            "
                        >
                            {{
                                response.answer_option.is_correct
                                    ? 'Helyes'
                                    : 'Helytelen'
                            }}
                        </Badge>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>
