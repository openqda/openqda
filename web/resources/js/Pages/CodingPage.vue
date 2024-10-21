<template>
    <AuthenticatedLayout :title="pageTitle" :menu="true" :showFooter="false" >
        <template #menu>
            <div class="flex">
                <Headline2 class="mt-3">Codes</Headline2>
            </div>
            <CodeList :codes="$props.allCodes" />
        </template>
        <template #main>
            <CodingEditor
                :source="$props.source"
                :codes="$props.allCodes"
            />
        </template>
    </AuthenticatedLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout.vue'
import Headline2 from '../Components/layout/Headline2.vue'
import CodeList from './coding/CodeList.vue'
import CodingEditor from './coding/CodingEditor.vue'

const pageTitle = ref('Coding')
const props = defineProps(['source', 'sources', 'codebooks', 'allCodes']);

onMounted(() => {
    const fileId = new URLSearchParams(window.location.search).get('source');
    if (fileId !== props.source.id) {
        // relocate?
    }
    onSourceSelected(props.source)
})

const onSourceSelected = (file) => {
    const url = new URL(location.href);
    if (url.searchParams.get('source') !== file.id) {
        url.searchParams.set('source', file.id);
        history.pushState(history.state, '', url);
    }
    pageTitle.value = `Coding: ${file.name}`;
}
</script>

<style scoped>
.contextMenuOption:hover {
    opacity: 0.7;
}

.editor-container {
    display: flex;
    flex-direction: row;
}

/* Dropdown menu item styles */
[role='menuitem'] {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    cursor: pointer;
    transition: opacity 0.2s ease-in-out;
}

[role='menuitem']:hover {
    opacity: 0.7;
}

.hide-scrollbar::-webkit-scrollbar {
    display: none;
}

.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

#dropzone {
    transform: translateY(0);
    transition: top 1.5s ease-in-out; /* Adjust timings if desired */
}

/** quill styles **/
/* Alignment Classes */

:deep(.ql-align-center) {
    text-align: center;
}

:deep(.ql-align-justify) {
    text-align: justify;
}

:deep(.ql-align-right) {
    text-align: right;
}

/* Formatting Classes */
:deep(.ql-bold) {
    font-weight: bold;
}

:deep(.ql-italic) {
    font-style: italic;
}

:deep(.ql-underline) {
    text-decoration: underline;
}

:deep(.ql-strike) {
    text-decoration: line-through;
}

:deep(.ql-code-block) {
    /* Your code block styles here */
}

:deep(.ql-blockquote) {
    /* Your blockquote styles here */
}

:deep(.ql-link) {
    /* Your link styles here */
}

/* List Classes */
:deep(.ql-list-ordered) {
    list-style-type: decimal;
}

:deep(.ql-list-bullet) {
    list-style-type: disc;
}

/* Text Size Classes */
:deep(.ql-size-small) {
    /* Smaller font size */
}

:deep(.ql-size-large) {
    /* Larger font size */
}

:deep(.ql-size-huge) {
    /* Even larger font */
}
</style>

<style lang="postcss" scoped>
#editor :deep(h1) {
    @apply text-2xl;
}

#editor :deep(h2) {
    @apply text-xl;
}

#editor :deep(h3) {
    @apply text-lg;
}

/*
  Enter and leave animations can use different
  durations and timing functions.
*/
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.8s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}
</style>
