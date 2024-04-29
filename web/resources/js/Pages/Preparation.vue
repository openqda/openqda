<template>
    <AppLayout>
        <Head title="Edit"></Head>
        <div class="flex w-screen h-screen">
            <!-- Left Side -->
            <div class="pt-2 text-ellipsis overflow-hidden" ref="leftSide" :style="{ width: leftWidth + 'px' }"
                 v-show="editorSourceRef.selected === false || focus === false">
                <!-- Top Left -->
                <FilesImporter
                    @fileSelected="onFileSelected($event)"
                    @documentDeleted="onDocumentDeleted"
                    class="border-b h-1/2 "
                />
            </div>
            <!-- Separator -->
            <div ref="separator" @mousedown="onMouseDown" class="antialiased bg-transparent cursor-ew-resize "
                 style="width: 8px;">
                <div class="h-screen bg-gray-200" style="width: 2px"></div>
            </div>
            <!-- Right Side -->
            <div ref="rightSide" class="overflow-auto transform transition-right duration-300 w-0"
                 :style="{ width: rightWidth + 'px' }">
                <div
                    v-show="editorSourceRef.selected === true">
                    <div class="flex items-center justify-between mt-3 align-center">
                        <!-- DATA DISPLAY -->
                        <Headline2>{{ editorSourceRef.name }}</Headline2>
                        <div class="flex items-center space-x-2 me-2">
                            <Button
                                v-if="editorSourceRef.CanUnlock && !editorSourceRef.hasSelections"
                                color="cerulean"
                                :icon=LockOpenIcon
                                :label="'Unlock to edit'"
                                @click="unlockSource"
                                class="px-1"
                            />
                            <Button

                                v-if="!editorSourceRef.CanUnlock && !editorSourceRef.hasSelections"
                                color="cerulean"
                                :icon=LockClosedIcon
                                :label="'Lock and Code'"
                                @click="lockAndCode"
                                class="px-1"
                            />
                            <Button
                                v-if="editorSourceRef.CanUnlock || (!editorSourceRef.CanUnlock && editorSourceRef.hasSelections)"
                                color="cerulean"
                                :icon=QrCodeIcon
                                :label="'Code This file'"
                                @click="codeThisFile"
                                class="px-1"
                            />
                            <Button
                                v-if="!editorSourceRef.CanUnlock && !editorSourceRef.hasSelections"
                                color="cerulean"
                                :icon=ArrowUpCircleIcon
                                :label="'Save'"
                                @click="saveQuillContent"
                                class="px-1"
                            />
                            <Button
                                v-if="focus === false"
                                color="cerulean"
                                :icon=ArrowsPointingOutIcon
                                @click="focus = true"
                                class="px-1"
                            />


                            <Button
                                v-if="focus === true"
                                color="cerulean"
                                :icon=ArrowsPointingInIcon
                                @click="focus = false"
                                class="px-1"
                            />
                            <Button
                                color="cerulean"
                                :icon=XMarkIcon
                                @click="editorSourceRef.selected = false;"
                            />
                        </div>
                    </div>

                    <div class="h-screen mt-4">
                        <PreparationsEditor ref="editorComponent" :source="editorSourceRef.content"
                                            :locked="editorSourceRef.locked"
                                            :CanUnlock="editorSourceRef.CanUnlock"/>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import {defineProps, inject, onBeforeUnmount, onMounted, provide, ref, unref, watch} from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PreparationsEditor from '../Components/preparation/PreparationsEditor.vue'
import FilesImporter from '../Components/files/FilesImporter.vue'
import Button from "../Components/interactive/Button.vue";
import {
    ArrowsPointingInIcon,
    ArrowsPointingOutIcon,
    ArrowUpCircleIcon,
    LockClosedIcon,
    LockOpenIcon,
    QrCodeIcon,
    XMarkIcon
} from "@heroicons/vue/20/solid";
import {Head, router, usePage} from "@inertiajs/vue3";
import Headline2 from '../Components/layout/Headline2.vue'
import {debounce} from '../utils/debounce.js'

const editorSourceRef = ref({
    content: 'select to display',
    selected: false,
    id: "",
    name: '',
    locked: true,
    CanUnlock: false,
    showLineNumbers: false,
    charsXLine: 80,
})

const focus = ref(false)
let documents = ref([]);
let editorComponent = ref();
const props = defineProps(['sources', 'newDocument']);
const editorContent = inject('editorContent');
const initialWidth = Math.round(
    Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0) / 2
);

const leftSide = ref(null);
const rightSide = ref(null);
const separator = ref(null);

const leftWidth = ref(Number(sessionStorage.getItem('preparation/leftWidth') || initialWidth));
const rightWidth = ref(Number(sessionStorage.getItem('preparation/rightWidth') || initialWidth)); // remaining width
let isResizing = false;

const onMouseDown = (e) => {
    isResizing = true;
    const innerDiv = separator.value.querySelector('div'); // Select the inner div
    if (innerDiv) {
        innerDiv.classList.add('bg-cerulean-700');  // Add class on mouse down
        innerDiv.classList.remove('bg-gray-200');  // Add class on mouse down
    }
    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
};

const onMouseMove = (e) => {
    if (!isResizing) return;
    const newLeftWidth = e.clientX;
    const newRightWidth = window.innerWidth - e.clientX;
    updateSeparator(newLeftWidth, newRightWidth)
};

const updateSeparator = (newLeftWidth, newRightWidth) => {
    if (Number.isNaN(newLeftWidth) || Number.isNaN(newRightWidth)) {
        return
    }
    leftWidth.value = Math.round(newLeftWidth);
    rightWidth.value = Math.round(newRightWidth);
    sessionStorage.setItem('preparation/leftWidth', newLeftWidth); // Save to local storage
    sessionStorage.setItem('preparation/rightWidth', newRightWidth); // Save to local storage
}

const onMouseUp = () => {
    isResizing = false;
    const innerDiv = separator.value.querySelector('div'); // Select the inner div
    if (innerDiv) {
        innerDiv.classList.remove('bg-cerulean-700');  // Add class on mouse down
        innerDiv.classList.add('bg-gray-200');  // Add class on mouse down
    }
    document.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('mouseup', onMouseUp);
};

watch((newValue) => {
    if (newValue !== null) {
        documents.value.push(newValue);
        onFileSelected(newValue)
    }
}, props.newDocument);

const lockAndCode = async () => {
    if (!confirm('Are you sure you want to lock the document and start coding? It cannot be unlocked once you started coding.')) {
        return;
    }
    router.post(route('source.lock', editorSourceRef.value.id))
}

function codeThisFile() {
    router.get(route('source.go-and-code', editorSourceRef.value.id))
}

const unlockSource = async () => {
    try {
        const response = await axios.post(`/sources/${editorSourceRef.value.id}/unlock`);
        usePage().props.flash.message = response.data.message;
        if (response.data.success) {
            editorSourceRef.value.locked = false
            editorSourceRef.value.CanUnlock = false


            // Handle the successful response

        } else {
            console.error("Failed to unlock the source:", response.data.message);
        }
    } catch (error) {
        usePage().props.flash.message = error.response.data.message
        console.error("An error occurred:", error);
    }
};


function onFileSelected(file) {
    if (!file?.content) {
        return
    }


    editorSourceRef.value.content = file.content
    editorSourceRef.value.selected = true
    editorSourceRef.value.id = file.id
    editorSourceRef.value.name = file.name
    editorSourceRef.value.locked = file.locked;
    editorSourceRef.value.CanUnlock = file.CanUnlock;
    editorSourceRef.value.hasSelections = file.hasSelections;
    editorSourceRef.value.showLineNumbers = file.showLineNumbers ?? false;
    editorSourceRef.value.charsXLine = file.charsXLine;
}

// Parent Component Script
function onDocumentDeleted(deletedDocumentId) {
    if (editorSourceRef.value.id === deletedDocumentId) {
        editorSourceRef.value.selected = false;
        editorSourceRef.value.content = '';  // Clear the editor content
        editorSourceRef.value.id = null;  // Clear the editor ID
    }
}


async function saveQuillContent() {

    try {
        const payload = {
            content: unref(editorComponent.value),
            id: editorSourceRef.value.id,
        };

        const response = await axios.post('/source/update', payload);
        usePage().props.flash.message = response.data.message;

        if (response.data.success) {
            // You can also set a local state variable to show a temporary message, if you like
        } else {
            // Handle the error case
        }
    } catch (error) {
        usePage().props.flash.message = error.response.data.message
        console.error('An error occurred while saving:', error);
        // Handle the error case
    }
}

async function saveLineNumbers() {

    try {
        const payload = {
            showLineNumbers: editorSourceRef.value.showLineNumbers ?? false,
            charsXLine: editorSourceRef.value.charsXLine,
            id: editorSourceRef.value.id,
        };

        const response = await axios.post(`/sources/${editorSourceRef.value.id}/linenumbers`, payload);
        usePage().props.flash.message = response.data.message;

        if (response.data.success) {
            // show line numbers in quill?
        } else {
            // Handle the error case
        }
    } catch (error) {
        console.error('An error occurred while saving:', error);
        // Handle the error case
    }

}

const getScale = (left, right) => {
    const newWidth = window.innerWidth
    const oldWidth = left + right
    return newWidth / oldWidth
}

const onWindowResize = debounce(() => {
    const scale = getScale(leftWidth.value, rightWidth.value)
    const currentLeft = leftWidth.value * scale
    const currentRight = rightWidth.value * scale
    updateSeparator(currentLeft, currentRight)
}, 100)

onMounted(() => {
    const scale = getScale(leftWidth.value, rightWidth.value)
    if (scale !== 1 && !Number.isNaN(scale)) {
        updateSeparator(
            leftWidth.value * scale,
            rightWidth.value * scale
        )
    }
    window.addEventListener('resize', onWindowResize);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('mouseup', onMouseUp);
    window.removeEventListener('resize', onWindowResize);
});

provide('sources', props.sources)
provide('newDocument', props.newDocument)
</script>
