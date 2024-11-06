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
import BaseContainer from '../Layouts/BaseContainer.vue';
import CreateDialog from '../dialogs/CreateDialog.vue';
import { useProjects } from './Projects/useProjects.js'

const props = defineProps(['audits']);
sessionStorage.clear();

const projectSelected = async () => {};
const { createProject, createSchema, open } = useProjects()
const createProjectSchema = ref(null);
</script>

<template>
  <AuthenticatedLayout title="Manage Projects" :menu="true">
    <template #menu>
      <BaseContainer>
        <ProjectsListMenu
          @selected="projectSelected"
          @create-project="
            createProjectSchema = createSchema
          "
        />
        <CreateDialog
          title="Create a new project"
          :schema="createProjectSchema"
          :submit="createProject"
          @cancelled="createProjectSchema = null"
          @created="({ response }) => open(response.data.project.id)"
        />
      </BaseContainer>
    </template>
    <template #main>
      <BaseContainer>
        <div class="flex items-center justify-center h-full text-foreground/50">
          <span>Select a project from the list or create a new one</span>
        </div>
      </BaseContainer>
    </template>
  </AuthenticatedLayout>
</template>
