<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Italic,
    Underline as UnderlineIcon,
    Strikethrough,
    Heading2,
    Heading3,
    List,
    ListOrdered,
    Quote,
    Link as LinkIcon,
    Unlink,
    Undo,
    Redo,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = defineProps<{
    modelValue?: string;
    name: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

// useEditor's returned ref only updates on mount/unmount, not on selection or
// content transactions, so toolbar active-state checks need an explicit
// reactive dependency to re-render when the editor's internal state changes.
const renderTick = ref(0);

const editor = useEditor({
    content: props.modelValue ?? '',
    extensions: [
        StarterKit.configure({
            link: { openOnClick: false, autolink: true },
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-neutral dark:prose-invert max-w-none min-h-32 px-3 py-2 text-sm focus:outline-none',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
    onTransaction: () => {
        renderTick.value++;
    },
});

function isActive(name: string, attrs?: Record<string, any>) {
    renderTick.value;
    return editor.value?.isActive(name, attrs) ?? false;
}

function can(check: 'undo' | 'redo') {
    renderTick.value;
    return editor.value?.can()[check]() ?? false;
}

watch(
    () => props.modelValue,
    (value) => {
        const current = editor.value?.getHTML();
        if (editor.value && value !== current) {
            editor.value.commands.setContent(value ?? '', { emitUpdate: false });
        }
    },
);

function toggleLink() {
    if (!editor.value) return;

    if (editor.value.isActive('link')) {
        editor.value.chain().focus().unsetLink().run();
        return;
    }

    const url = window.prompt('Link URL');
    if (!url) return;

    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <div
        class="rounded-md border border-input bg-transparent shadow-xs focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50"
    >
        <div
            v-if="editor"
            class="flex flex-wrap items-center gap-1 border-b border-input p-1"
        >
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="cn(isActive('bold') && 'bg-accent')"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="cn(isActive('italic') && 'bg-accent')"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="cn(isActive('underline') && 'bg-accent')"
                @click="editor.chain().focus().toggleUnderline().run()"
            >
                <UnderlineIcon />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="cn(isActive('strike') && 'bg-accent')"
                @click="editor.chain().focus().toggleStrike().run()"
            >
                <Strikethrough />
            </Button>

            <div class="mx-1 h-5 w-px bg-border" />

            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="cn(isActive('heading', { level: 2 }) && 'bg-accent')"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
            >
                <Heading2 />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="cn(isActive('heading', { level: 3 }) && 'bg-accent')"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
            >
                <Heading3 />
            </Button>

            <div class="mx-1 h-5 w-px bg-border" />

            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="cn(isActive('bulletList') && 'bg-accent')"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="cn(isActive('orderedList') && 'bg-accent')"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="cn(isActive('blockquote') && 'bg-accent')"
                @click="editor.chain().focus().toggleBlockquote().run()"
            >
                <Quote />
            </Button>

            <div class="mx-1 h-5 w-px bg-border" />

            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="cn(isActive('link') && 'bg-accent')"
                @click="toggleLink"
            >
                <Unlink v-if="isActive('link')" />
                <LinkIcon v-else />
            </Button>

            <div class="mx-1 h-5 w-px bg-border" />

            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :disabled="!can('undo')"
                @click="editor.chain().focus().undo().run()"
            >
                <Undo />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :disabled="!can('redo')"
                @click="editor.chain().focus().redo().run()"
            >
                <Redo />
            </Button>
        </div>

        <EditorContent :editor="editor" />

        <input type="hidden" :name="name" :value="modelValue ?? ''" />
    </div>
</template>
