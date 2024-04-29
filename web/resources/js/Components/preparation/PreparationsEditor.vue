<template>
    <div id="editor"></div>
</template>

<script setup>
import {onMounted, onUnmounted, ref, watch} from "vue";
import Quill from "quill";
import QuillCursors from "quill-cursors";


let quillInstance;
const editorContent = ref("")

Quill.register("modules/cursors", QuillCursors);


onMounted(() => {
    quillInstance = new Quill("#editor", {
        theme: "snow",
        modules: {
            syntax: false,
            history: {
                delay: 2000,
                maxStack: 500,
                userOnly: true
            },
            toolbar: {
                container: [
                    ["bold", "italic", "underline", "strike"], // toggled buttons
                    ["blockquote", "code-block"],

                    [{header: 1}, {header: 2}], // custom button values
                    [{list: "ordered"}, {list: "bullet"}],
                    [{script: "sub"}, {script: "super"}], // superscript/subscript
                    [{indent: "-1"}, {indent: "+1"}], // outdent/indent
                    [{direction: "rtl"}], // text direction

                    [{size: ["small", false, "large", "huge"]}], // custom dropdown
                    [{header: [1, 2, 3, 4, 5, 6, false]}],

                    [{color: []}, {background: []}], // dropdown with defaults from theme
                    [{font: []}],
                    [{align: []}],

                    ["clean"] // remove formatting button,
                ]
            }
        }
    });

    quillInstance.on("text-change", function (delta, oldDelta, source) {
        if (source === "user") {
            // Add custom logic here
        }
    });

    quillInstance.on("editor-change", function (eventName, ...args) {
        editorContent.value = quillInstance.root.innerHTML;

    });



quillInstance.enable(!props.locked)
});


onUnmounted(() => {
    if (quillInstance) {
        quillInstance = null;
    }
});

const props = defineProps({
    source: String, locked: Boolean, CanUnlock: Boolean
})

watch(() => props.source, (newValue, oldValue) => {
    //quillInstance.setText(newValue)
    quillInstance.clipboard.dangerouslyPasteHTML(newValue);
})

watch(() => props.locked, (newValue, oldValue) => {
    //quillInstance.setText(newValue)

    quillInstance.enable(!newValue)
})


defineExpose(editorContent);
</script>

<style scoped>
.ql-container.ql-snow{ border: none !important;}
</style>
