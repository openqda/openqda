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
import BaseContainer from '../Layouts/BaseContainer.vue';

const props = defineProps(['audits']);
const newProjectForm = ref(false);
const showContent = ref(false);

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
      <BaseContainer>
        <ProjectsListMenu
          @selected="projectSelected"
          @create-project="() => projectForm(true)"
        />
      </BaseContainer>
    </template>
    <template #main>
      <BaseContainer>
        <CreateProjectForm
          v-if="newProjectForm"
          class="w-100 block"
          @cancelled="() => projectForm(false)"
        />
        <div
          v-else
          class="flex items-center justify-center h-full text-foreground/50"
        >
          <span>Select a project from the list or create a new one</span>
        </div>
      </BaseContainer>
    </template>
  </AuthenticatedLayout>
</template>
