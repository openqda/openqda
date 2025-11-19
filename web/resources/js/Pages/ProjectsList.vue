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
import { useProjects } from '../domain/project/useProjects.js';
import HelpResources from '../Components/HelpResources.vue';
import Headline2 from '../Components/layout/Headline2.vue';
import Footer from '../Layouts/Footer.vue';
sessionStorage.clear();

const projectSelected = async () => {};
const { createProject, createSchema, open } = useProjects();
const createProjectSchema = ref(null);
</script>

<template>
  <AuthenticatedLayout title="Manage Projects" :menu="true">
    <template #menu>
      <BaseContainer>
        <ProjectsListMenu
          @selected="projectSelected"
          @create-project="createProjectSchema = createSchema"
        />
        <CreateDialog
          title="Create a new project"
          :schema="createProjectSchema"
          :submit="createProject"
          @cancelled="createProjectSchema = null"
          @created="({ response }) => open(response.data.project.id)"
        />
        <div class="mt-auto">
          <Footer />
        </div>
      </BaseContainer>
    </template>
    <template #main>
      <BaseContainer>
        <div class="flex items-center justify-center h-full text-foreground/50">
          <div>
            <Headline2>Your projects</Headline2>
            <div class="mt-4 mb-8 block">
              Select a project from the list or create a new one. You can search
              for project titles, descriptions and ids.
            </div>
            <HelpResources class="flex flex-col gap-4" />
          </div>
        </div>
      </BaseContainer>
    </template>
  </AuthenticatedLayout>
</template>
