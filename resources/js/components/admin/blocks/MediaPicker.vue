<script setup lang="ts">
import { ref } from 'vue';
import { Upload } from '@lucide/vue';
import CourseMediaController from '@/actions/App/Http/Controllers/Admin/CourseMediaController';
import { Button } from '@/components/ui/button';
import { mediaUrl } from '@/lib/blocks/media';
import { uploadMedia } from '@/lib/blocks/upload';

const props = defineProps<{
    modelValue: string;
    courseId: number;
    accept?: string;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const uploading = ref(false);
const error = ref<string | null>(null);

async function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    uploading.value = true;
    error.value = null;

    try {
        const { path } = await uploadMedia(
            CourseMediaController.store.url(props.courseId),
            file,
        );

        emit('update:modelValue', path);
    } catch {
        error.value = 'A feltöltés nem sikerült.';
    } finally {
        uploading.value = false;
        input.value = '';
    }
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <div v-if="modelValue" class="flex items-center gap-2">
            <img
                v-if="/\.(png|jpe?g|gif|webp|svg)$/i.test(modelValue)"
                :src="mediaUrl(modelValue)"
                class="h-16 w-16 rounded-md border object-cover"
                alt=""
            />
            <span class="truncate text-xs text-muted-foreground">{{
                modelValue
            }}</span>
        </div>

        <label>
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="uploading"
                as="span"
                class="cursor-pointer"
            >
                <Upload class="size-3.5" />
                {{ uploading ? 'Feltöltés…' : modelValue ? 'Csere' : 'Feltöltés' }}
            </Button>
            <input
                type="file"
                class="hidden"
                :accept="accept"
                @change="onFileChange"
            />
        </label>

        <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
    </div>
</template>
