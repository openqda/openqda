<script setup>
import { Project } from '../../domain/Project';
import Headline2 from '../../Components/layout/Headline2.vue';
import AutoForm from '../../form/AutoForm.vue';
import { onMounted, ref, useTemplateRef } from 'vue';
import { flashMessage } from '../../Components/notification/flashMessage.js';
import { Routes } from '../../routes/Routes.js';
import ScrollIntoView from '../../Components/layout/ScrollIntoView.vue';

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
  <scroll-into-view behavior="smooth" halign="start" valign="start">
    <Headline2 class="mt-2 mb-4 text-primary">Create new Project</Headline2>
    <AutoForm
      id="create-project-form"
      :autofocus="true"
      :schema="Project.create.schema"
      @cancel="$emit('cancelled')"
      @submit="addProject"
      class="w-full lg:w-3/4 xl:w-1/2"
    >
    </AutoForm>
  </scroll-into-view>
</template>
