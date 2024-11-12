<template>
    <AuthenticatedLayout :title="pageTitle" :menu="true" :showFooter="false">
        <template #menu>
            <BaseContainer>
                <ActivityIndicator v-if="!codingInitialized">
                    Loading codes and selections...
                </ActivityIndicator>
                <div v-else class="flex items-center justify-between">
                    <Button
                        variant="outline-secondary"
                        @click="openCreateDialogHandler(codesView)"
                    >
                        <PlusIcon class="w-4 h-4 me-1"/>
                        <span v-if="codesView === 'codes' && range?.length"
                        >Create In-Vivo</span
                        >
                        <span v-else>Create</span>
                    </Button>
                    <CreateDialog
                        :schema="createSchema"
                        :title="`Create a new ${codesView === 'codes' ? 'Code' : 'Codebook'}`"
                        :submit="createCodeHandler"
                        @cancelled="createSchema = null"
                    />
                    <CreateDialog
                        :title="`Edit ${editTarget?.name}`"
                        :schema="editSchema"
                        buttonTitle="Update code"
                        :submit="updateCode"
                    />
                    <DeleteDialog
                        :title="`Permanently delete ${deleteTarget?.name}`"
                        :target="deleteTarget"
                        :challenge="deleteChallenge"
                        :message="deleteMessage"
                        :submit="deleteCode"
                    />
                    <ResponsiveTabList
                        :tabs="codesTabs"
                        :initial="codesView"
                        @change="(value) => (codesView = value)"
                    />
                </div>
                <CodeList
                    v-for="codebook in codebooks"
                    :codebook="codebook"
                    :codes="codes.filter((code) => code.codebook === codebook.id)"
                    v-if="codesView === 'codes'"
                />
                <FilesList
                    v-if="codesView === 'sources'"
                    :documents="sourceDocuments"
                    :fixed="true"
                    :focus-on-hover="true"
                    :actions="[]"
                    @select="switchFile"
                />
                <p v-if="codesView === 'sources' && !sourceDocuments?.length"
                    class="p-3 text-foreground/60">
                    No other sources locked for coding
                </p>
            </BaseContainer>
        </template>
        <template #main>
            <CodingEditor
                :project="{ id: projectId }"
                :source="$props.source"
                :codes="$props.allCodes"
            />
        </template>
    </AuthenticatedLayout>
</template>

<script setup>
import {PlusIcon} from '@heroicons/vue/24/solid'
import { onMounted, onUpdated, ref } from 'vue'
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout.vue'
import CodeList from './coding/CodeList.vue'
import CodingEditor from './coding/CodingEditor.vue'
import BaseContainer from '../Layouts/BaseContainer.vue'
import ResponsiveTabList from '../Components/lists/ResponsiveTabList.vue'
import Button from '../Components/interactive/Button.vue'
import {useCodes} from './coding/useCodes.js'
import {useRange} from './coding/useRange.js'
import {useRenameDialog} from '../dialogs/useRenameDialog.js'
import {useDeleteDialog} from '../dialogs/useDeleteDialog.js'
import CreateDialog from '../dialogs/CreateDialog.vue'
import {Codes} from './coding/codes/Codes.js'
import DeleteDialog from '../dialogs/DeleteDialog.vue'
import {useSelections} from './coding/selections/useSelections.js'
import FilesList from '../Components/files/FilesList.vue'
import {router} from '@inertiajs/vue3'
import { asyncTimeout } from '../utils/asyncTimeout.js'
import ActivityIndicator from '../Components/ActivityIndicator.vue'
import {useCreateDialog} from '../dialogs/useCreateDialog.js'

const props = defineProps([
    'source',
    'sources',
    'codebooks',
    'allCodes',
    'projectId'
])

//------------------------------------------------------------------------
// SOURCES
//------------------------------------------------------------------------
const sourceDocuments = ref(props.sources.filter(source => {
    if (source.id === props.source.id) return false
    if (source.isLocked) return true
    return (source.variables ?? []).find(({ name, boolean_value }) => name === 'isLocked' && boolean_value === 1)
}).map(source => {
    const copy = { ...source }
    copy.date = new Date(source.updated_at).toLocaleDateString()
    copy.variables = { isLocked: true }
    copy.isConverting = false
    copy.failed = false
    copy.converted = true
    return copy
}))
const switchFile = (file) => {
    router.get(route('source.code', file.id));
}
//------------------------------------------------------------------------
// GENERIC EDIT DIALOG
//------------------------------------------------------------------------
const { schema: createSchema, open:openCreateDialog } = useCreateDialog()
const { schema: editSchema, target: editTarget } = useRenameDialog()
const { target: deleteTarget, challenge: deleteChallenge, message: deleteMessage } = useDeleteDialog()

//------------------------------------------------------------------------
// RANGE / SELECTION
//------------------------------------------------------------------------
const { range, text, prevRange } = useRange()
const { createSelection } = useSelections()

//------------------------------------------------------------------------
// CODES / CODEBOOKS
//------------------------------------------------------------------------
const { codes, codebooks, createCode, createCodeSchema, updateCode, deleteCode, initCoding } = useCodes()
const codingInitialized = ref(false)
const codesTabs = [
    { value: 'codes', label: 'Codes' },
    { value: 'sources', label: 'Sources' }
]
const codesView = ref(codesTabs[0].value)
const openCreateDialogHandler = (view) => {
    if (view === 'codes') {
        openCreateDialog({
            schema: {
                title: text?.value,
                codebooks: codebooks.value,
            }
        })
    }
}
const createCodeHandler = async (formData) => {
    const code = await createCode(formData)
    const txt = createSchema.value.title.defaultValue
    const { index, length } = prevRange.value ?? {}

    // immediately apply in-vivo codes as selections
    if (code && txt && length) {
        try {
            await createSelection({
                code,
                text: txt,
                index,
                length
            })
        } catch (e) {
            console.error(e)
        }
    }
    return code
}

//------------------------------------------------------------------------
// PAGE
//------------------------------------------------------------------------
const pageTitle = ref('Coding')
const url = window.location.pathname
const segments = url.split('/')
const projectId = segments[2] // Assuming project id is the third segment in URL path

onMounted(async () => {
    const fileId = new URLSearchParams(window.location.search).get('source')
    if (fileId !== props.source.id) {
        // relocate?
    }
    console.debug('select source')
    onSourceSelected(props.source)
    console.debug('timeout')
    await asyncTimeout(100)
    console.debug('init coding')
    await initCoding()
    console.debug('set init to true')
    codingInitialized.value = true
})

const onSourceSelected = (file) => {
    codingInitialized.value = false
    const url = new URL(location.href)
    if (url.searchParams.get('source') !== file.id) {
        url.searchParams.set('source', file.id)
        history.pushState({ ...history.state }, '', url)
    }
    pageTitle.value = `Coding of ${file.name}`
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

