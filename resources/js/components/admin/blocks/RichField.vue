<script setup lang="ts">
import { Label } from '@/components/ui/label';
import RichTextEditor from '@/components/admin/RichTextEditor.vue';

defineProps<{ label: string; modelValue: string; name: string }>();
defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <div class="grid gap-1.5">
        <Label>{{ label }}</Label>
        <RichTextEditor
            :name="name"
            :model-value="modelValue"
            @update:model-value="$emit('update:modelValue', $event)"
        />
    </div>
</template>
