<script setup>
/*
 |--------------------------------------------------------------------------
 | Projects List
 |--------------------------------------------------------------------------
 | Page-level component, that shows all projects
 | for the current signed-in user.
 */
import { ref } from 'vue';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout.vue';
import ProjectsListMenu from './Projects/ProjectsListMenu.vue';
import CreateProjectForm from './Projects/CreateProjectForm.vue';

const props = defineProps(['projects', 'audits']);

const newProjectForm = ref(false);
const showContent = ref(false);
const showHistory = ref(false);

sessionStorage.clear();

const projectForm = (value) => {
  showContent.value = value;
  newProjectForm.value = value;
};

const projectSelected = async () => {};
</script>

<template>
    <AuthenticatedLayout title="Manage Projects" :menu="true">
        <template #menu>
            <ProjectsListMenu
                :projects="projects"
                @selected="projectSelected"
                @create-project="() => projectForm(true)"
            />
        </template>
        <template #main>
            <div v-if="showContent" class="p-5">
                <CreateProjectForm
                    v-if="newProjectForm"
                    class="w-100 block"
                    @cancelled="() => projectForm(false)"
                />
            </div>
        </template>
    </AuthenticatedLayout>
</template>
