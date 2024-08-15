<script setup>
import { Project } from '../../domain/Project';
import Headline2 from '../../Components/layout/Headline2.vue';
import AutoForm from '../../ui/form/AutoForm.vue';
import { ref } from 'vue';
import { flashMessage } from '../../Components/notification/flashMessage.js';
import { Routes } from '../../routes/Routes.js';

defineEmits(['created', 'cancelled']);

const newProject = ref({
  name: '',
  description: '',
});
const addProject = async () => {
  try {
    const { project, message } = await Project.create.call(newProject.value);
    flashMessage(message);
    if (project) {
      // Add the new project to the projects list
      // but also attempt to auto-redirect
      projectsComputed.value.push(project);
      setTimeout(() => {
        window.location = Routes.project.path(project.id);
      }, 1000);
    }
  } catch (e) {
    flashMessage(e.message, { type: 'error ' });
  }
};
</script>

<template>
  <Headline2 class="mt-2 mb-4">Create new Project</Headline2>
  <AutoForm
    id="create-project-form"
    :schema="Project.create.schema"
    @cancel="$emit('cancelled')"
    @submit="addProject"
  >
  </AutoForm>
</template>
