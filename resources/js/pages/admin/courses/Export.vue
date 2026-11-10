<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Download, FileText } from '@lucide/vue';
import { computed, ref } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import PublicCourseController from '@/actions/App/Http/Controllers/CourseController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const props = defineProps<{
    courses: {
        id: number;
        title: string;
        slug: string;
    }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin – Kurzusok', href: CourseController.index() },
            { title: 'Export', href: CourseController.exportIndex() },
        ],
    },
});

type ExportFormat = 'zip' | 'pdf';

const selectedCourseId = ref(
    props.courses[0] ? String(props.courses[0].id) : '',
);
const selectedFormat = ref<ExportFormat>('zip');

const selectedCourse = computed(() =>
    props.courses.find(
        (course) => String(course.id) === selectedCourseId.value,
    ),
);

const exportUrl = computed(() => {
    if (!selectedCourse.value) {
        return null;
    }

    return selectedFormat.value === 'zip'
        ? CourseController.export(selectedCourse.value.id).url
        : PublicCourseController.pdf(selectedCourse.value.slug).url;
});
</script>

<template>
    <Head title="Admin – Kurzus exportálása" />

    <div class="flex flex-col gap-6 p-4">
        <AdminHeader
            :icon="Download"
            title="Kurzus exportálása"
            description="Válassz egy kurzust és egy formátumot a letöltéshez."
            :back-href="CourseController.index()"
            back-label="Vissza a kurzusokhoz"
        />

        <Card class="max-w-xl p-6">
            <div v-if="courses.length === 0" class="py-8 text-center">
                <p class="font-medium">Még nincs kurzus</p>
            </div>

            <div v-else class="flex flex-col gap-4">
                <div class="grid gap-2">
                    <Label>Kurzus</Label>
                    <Select v-model="selectedCourseId">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Válassz kurzust" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="course in courses"
                                :key="course.id"
                                :value="String(course.id)"
                            >
                                {{ course.title }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="grid gap-2">
                    <Label>Formátum</Label>
                    <Select v-model="selectedFormat">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Válassz formátumot" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="zip">
                                ZIP (importáláshoz)
                            </SelectItem>
                            <SelectItem value="pdf">
                                PDF (megosztáshoz, nyomtatáshoz)
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <a v-if="exportUrl" :href="exportUrl" class="self-start">
                    <Button>
                        <component
                            :is="selectedFormat === 'zip' ? Download : FileText"
                            class="size-4"
                        />
                        Letöltés
                    </Button>
                </a>
            </div>
        </Card>
    </div>
</template>
