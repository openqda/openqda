<script setup lang="ts">

import Button from "../Components/interactive/Button.vue";
import DialogBase from "./DialogBase.vue";
import {ref, watch} from "vue";
import FilesImportWizard from "../Components/files/FilesImportWizard.vue";

const emit = defineEmits(['filesSelected', 'cancelled']);
const error = ref(null);
const complete = ref(false);
const submitting = ref(false);
const open = ref(false);
const props = defineProps({
    schema: { type: Object },
    submit: { type: Function },
    title: { type: String, required: false },
});
watch(
    () => props.schema,
    (newSchema) => newSchema && start(newSchema)
);

const start = (newSchema) => {
    open.value = true
};
const cancel = () => {
    open.value = false;
    error.value = null;
    submitting.value = false;
    complete.value = false;
    emit('cancelled');
};

</script>

<template>
    <DialogBase :show="open">
        <template #title>Import file(s)</template>
        <template #body>
           <FilesImportWizard class="border-t border-foreground/10 pt-4" @files-selected="files => emit('filesSelected', files) || cancel()"/>
        </template>
        <template #close>
            <Button variant="ghost" size="sm" @click="cancel">&times;</Button>
        </template>
    </DialogBase>
</template>

<style scoped>

</style>
