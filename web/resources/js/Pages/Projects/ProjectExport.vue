<script setup lang="ts">
import {ref} from "vue";
import Checkbox from "../../form/Checkbox.vue";
import SelectField from "../../form/SelectField.vue";
import Button from "../../Components/interactive/Button.vue";
import Collapse from "../../Components/layout/Collapse.vue";
import { ChevronRightIcon } from "@heroicons/vue/24/solid";
import {useProjects} from "../../domain/project/useProjects";
import ActivityIndicator from "../../Components/ActivityIndicator.vue";

const props = defineProps(['project']);
const exportError = ref(null);
const exporting = ref(false);
const { exportProject } = useProjects();
const schema = ref([
    {
        name: 'Project',
        included: ['ID', 'Name', 'Created At', 'Created By', 'Updated At'],
        optional: [{
            value: 'description',
            label: 'Description',
        }, {
            value: 'origin',
            label: 'Created with OpenQDA'
        }]
    },
    {
        name: 'Users',
        included: ['ID', 'Name'],
        optional: [{
            value: 'email',
            label: 'Email',
        }],
        choices: [
            {
                value: 'name',
                options: [{value: 'real', label: 'Use real names'}, {value: 'fake', label: 'Obfuscate names'}]
            },
        ]
    },
    {
        name: 'Codebooks',
        included: ['ID', 'name', 'Created At', 'Created By', 'Updated At'],
        optional: [{
            value: 'description',
            label: 'Description',
        }]
    },
    {
        name: 'Selections',
        included: ['ID', 'name', 'Indexes', 'Created At', 'Created By', 'Updated At'],
        optional: [{
            value: 'description',
            label: 'Description',
        }]
    },
]);

const runExport = async (e) => {
    e.preventDefault();
    exportError.value = null;
    exporting.value = true;

    const { response, error } = await exportProject({
        projectId: props.project.id
    });
    if (!error && response?.data) {
        // Create a link to download the file
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        const timestamp = Date.now();
        const title = props.project.name.replace(/\s+/g, '-');
        link.href = url;
        link.setAttribute('download', `OpenQDA-${title}-${timestamp}.qdpx`); //or any other extension
        document.body.appendChild(link);
        link.click();
        link.remove();
    } else {
        exportError.value = error ?? new Error('Export failed for unknown reasons');
    }
    exporting.value = false;
};
</script>

<template>
    <div>
        <p class="text-sm text-foreground/60 me-3 my-4 py-4">
            You can export your project as a REFI-compliant archive,
            which you can import in other REFI compliant QDA software.
            You may deselect non-standard and optional fields.
        </p>
        <Collapse :default-open="false" class="w-full">
            <template #trigger="{ open }">
                <div class="text-sm text-foreground/60 flex items-center cursor-pointer select-none">
                    <ChevronRightIcon :class="`w-3 h-3 me-1 transition-transform duration-200 ${open ? 'rotate-90' : ''}`"/>
                    <span class="my-4 py-1">Export options</span>
                </div>
            </template>
            <template #content>
        <div class="flex justify-between">
            <div v-for="(item, index) in schema" :key="index">
                <h3 class="font-semibold text-foreground/80 tracking-wide">{{ item.name }}</h3>
                <div class="text-xs tracking-wide border-b w-full my-2  text-foreground/60">REFI Standard Fields</div>
                <Checkbox v-for="(option, idx) in item.included" disabled="" :key="idx" :value="option"
                          label-class="text-sm  text-foreground/50" :label="option" :default-value="true"/>

                <div class="text-xs tracking-wide border-b w-full my-2  text-foreground/60">OpenQDA Specific Fields</div>
                <Checkbox v-for="(option, idx) in item.optional" :key="idx" :value="option.value"
                          label-class="text-sm  text-foreground cursor-pointer" :label="option.label"
                          :default-value="false" />

                <div v-if="item.choices?.length" class="text-xs tracking-wide border-b w-full my-2  text-foreground/60">Additional Options</div>
                <SelectField v-for="(choice, idx) in item.choices" :key="idx" :name="choice.value"
                             class="mt-4 text-foreground"
                             :options="choice.options" :value="choice.options[0].value"/>
            </div>
        </div>
            </template>
        </Collapse>
        <div v-if="exportError" class="text-destructive mt-2">
            {{ exportError.message }}
        </div>
        <div class="my-4 py-4">
            <span class="flex gap-1 items-center float-end">
          <ActivityIndicator v-if="exporting" :label="false" />
            <Button
                :disabled="exporting"
                @click="runExport"
            >Export Project</Button>
            </span>
        </div>
    </div>
</template>

<style scoped>

</style>
