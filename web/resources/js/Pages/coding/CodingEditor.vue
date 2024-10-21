<template>
    <!-- editor toolbar -->
    <div class="block xl:flex lg:justify-center sticky top-0 py-2 z-40 bg-surface">
        <div
            id="toolbar"
            class="rounded-none mb-3 xl:mb-0 lg:rounded-full border-2 bg-surface z-150 shadow-lg border-foreground/20 py-2 px-4 inline-flex !text-foreground/60"
        >
            <EditorToolbar />
        </div>
        <slot name="actions"></slot>
    </div>
    <!-- editor content -->
    <div class="flex">
        <div id="lineNumber"></div>
        <div
            id="editor"
            class="flex-grow"
            @contextmenu.prevent="showContextMenu"
            @dragenter.prevent
            @dragover.prevent
            @drop.prevent="console.debug"
        ></div>
    </div>
    <div class="absolute bottom-10 right-10" style="z-index: 999;">
        <slot name="status"></slot>
        <span id="selection-hash"
              class="w-6 h-6 text-center text-xs text-foreground/60 border-0 bg-surface p-2">0:0</span>
    </div>
    <CodingContextMenu
        @close="contextMenuVisible = false"
        @codes-selected="codeSelected"
        :codes="$props.codes"
        :visible="contextMenuVisible" />
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import Quill from 'quill';
import QuillCursors from 'quill-cursors';
import { formats, redoChange, undoChange } from '../../editor/EditorConfig.js';
import '../../editor/editor.css';
import { LineNumber } from '../../editor/LineNumber.js'
import { SelectionHash } from '../../editor/SelectionHash.js'
import EditorToolbar from '../../editor/EditorToolbar.vue'
import CodingContextMenu from './CodingContextMenu.vue'
import { useTextSelection } from '@vueuse/core'

let quillInstance;
const Delta = Quill.import('delta');
const editorContent = ref('');
const contextMenuVisible = ref(false)
const textSelection = useTextSelection()

Quill.register('modules/lineNumber', LineNumber, true);
Quill.register('modules/selectionHash', SelectionHash, true);
Quill.register('modules/cursors', QuillCursors);

const emit = defineEmits(['code-assigned']);
const props = defineProps({
    source: Object,
    codes:Array,
    locked: Boolean,
    CanUnlock: Boolean,
});

onMounted(() => {
    quillInstance = new Quill('#editor', {
        theme: 'snow',
        formats,
        placeholder: 'Start writing or paste content...',
        modules: {
            syntax: false,
            history: {
                delay: 2000,
                maxStack: 500,
                userOnly: true,
            },
            toolbar: {
                container: '#toolbar',
                handlers: {
                    undo: undoChange,
                    redo: redoChange,
                },
            },
            lineNumber: {
                container: '#lineNumber'
            },
            selectionHash: {
                container: '#selection-hash'
            }
        },
    });

    quillInstance.enable(false);
    quillInstance.clipboard.dangerouslyPasteHTML(props.source.content);
});

onUnmounted(() => {
    if (quillInstance) quillInstance = null;
});

watch(
    () => props.source,
    (newValue /*, oldValue*/) => {
        //quillInstance.setText(newValue)
        quillInstance.clipboard.dangerouslyPasteHTML(newValue.content);
    }
);

const showContextMenu = (event) => {
    contextMenuVisible.value = true
    console.debug(textSelection.ranges.value)
    console.debug(textSelection.text.value)
    console.debug(textSelection.rects.value)
    console.debug(textSelection.selection.value)
    const contextMenu = document.getElementById('contextMenu');
    const windowHeight = window.innerHeight;

    contextMenu.style.left = `${event.clientX}px`;
    contextMenu.classList.remove('hidden');

    // Force a slight layout update so we can measure the contextMenu's dimensions
    contextMenu.offsetHeight;

    if (event.clientY + contextMenu.offsetHeight > windowHeight) {
        // If the context menu would go out of bounds, adjust its top position
        contextMenu.style.top = `${windowHeight - contextMenu.offsetHeight}px`;
    } else {
        contextMenu.style.top = `${event.clientY}px`;
    }
}

const codeSelected = (index, codeId, parentId = null) => {

}

defineExpose({ editorContent });
</script>

<style scoped>
.ql-container.ql-snow {
    line-height: 18.4667;
    border: none !important;
}

#lineNumber {
    text-align: end;
    font-size: 10px;
    font-family: "Lucida Console", monospace, sans-serif;
    padding: 12px 5px;
    line-height: 18.4667;
    vertical-align: top;
    box-sizing: border-box;
}
</style>
