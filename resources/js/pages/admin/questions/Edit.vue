<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import LessonController from '@/actions/App/Http/Controllers/Admin/LessonController';
import QuestionController from '@/actions/App/Http/Controllers/Admin/QuestionController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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

    <div class="flex flex-col gap-6 p-4">
        <div>
            <Link
                :href="LessonController.edit(lesson.id)"
                class="text-sm text-muted-foreground hover:underline"
            >
                &larr; {{ lesson.title }}
            </Link>
            <h1 class="text-xl font-semibold tracking-tight">
                {{ question ? 'Kérdés szerkesztése' : 'Új kérdés' }}
            </h1>
        </div>

        <Form
            v-bind="
                question
                    ? QuestionController.update.form(question.id)
                    : QuestionController.store.form(lesson.id)
            "
            class="flex max-w-xl flex-col gap-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="text">Kérdés szövege</Label>
                <textarea
                    id="text"
                    name="text"
                    rows="2"
                    required
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >{{ question?.text }}</textarea>
                <InputError :message="errors.text" />
            </div>

            <div class="grid gap-2">
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

                <div
                    v-for="(option, index) in options"
                    :key="index"
                    class="flex items-center gap-2"
                >
                    <input
                        type="radio"
                        name="correct_option"
                        :value="index"
                        :checked="index === correctIndex"
                        @change="correctIndex = index"
                        class="accent-primary"
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
                        class="flex-1"
                        required
                    />
                    <Button
                        v-if="options.length > 2"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="removeOption(index)"
                    >
                        Törlés
                    </Button>
                </div>

                <InputError :message="errors.options" />
                <InputError :message="errors.correct_option" />

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="w-fit"
                    @click="addOption"
                >
                    + Opció hozzáadása
                </Button>
            </div>

            <Button type="submit" :disabled="processing" class="w-fit"
                >Mentés</Button
            >
        </Form>

        <div
            v-if="question && responses.length > 0"
            class="flex max-w-xl flex-col gap-3"
        >
            <h2 class="text-lg font-semibold tracking-tight">
                Beérkezett válaszok
            </h2>

            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-2">Felhasználó</th>
                        <th class="p-2">Válasz</th>
                        <th class="p-2">Helyes?</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="response in responses"
                        :key="response.id"
                        class="border-t border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <td class="p-2">{{ response.user.name }}</td>
                        <td class="p-2">{{ response.answer_option.text }}</td>
                        <td class="p-2">
                            {{
                                response.answer_option.is_correct
                                    ? 'Igen'
                                    : 'Nem'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
