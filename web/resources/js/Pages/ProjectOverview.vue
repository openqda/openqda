<template>
  <AuthenticatedLayout :title="name" :menu="true">
    <template #menu>
      <BaseContainer>
        <ProjectsListMenu
          :projects="projects"
          @create-project="createProjectSchema = createSchema"
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
        <ResponsiveTabList
          :tabs="tabs"
          :initial="currentSubView"
          @change="changeCurrentView"
        />
        <ProjectSummary v-if="currentSubView === 'overview'" />
        <ProjectTeams v-if="currentSubView === 'collab'" :project="project" />
        <ProjectCodebooks v-if="currentSubView === 'codebooks'" />
        <Audit
          v-if="currentSubView === 'history'"
          :project-id="projectId"
          context="projectPage"
        />
      </BaseContainer>
    </template>
  </AuthenticatedLayout>
</template>

<script setup>
/*
 |--------------------------------------------------------------------------
 | Project Overview
 |--------------------------------------------------------------------------
 | Page-level component, that represents the current project and allows
 | to manage settings, teams, codebooks and audits.
 */
import { onBeforeUnmount, onMounted, ref, provide } from 'vue';
import Audit from '../Components/global/Audit.vue';
import ProjectTeams from './Teams/ProjectTeams.vue';
import ProjectsListMenu from './Projects/ProjectsListMenu.vue';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout.vue';
import ProjectSummary from './Projects/ProjectSummary.vue';
import ProjectCodebooks from './Projects/codebooks/ProjectCodebooks.vue';
import ResponsiveTabList from '../Components/lists/ResponsiveTabList.vue';
import BaseContainer from '../Layouts/BaseContainer.vue';
import CreateDialog from '../dialogs/CreateDialog.vue';
import { useProjects } from '../domain/project/useProjects.js';

const props = defineProps(['project', 'projects']);

const name = ref(props.project.name);
const url = window.location.pathname;
const segments = url.split('/');
let projectId = segments[2]; // Assuming project id is the third segment in URL path
const { createProject, createSchema, open } = useProjects();
const createProjectSchema = ref(null);
provide('project', props.project);

const Tabs = {
  overview: {
    label: 'Overview',
    href: '#overview',
  },
  collab: {
    label: 'Collaboration',
    href: '#collab',
  },
  codebooks: {
    label: 'Codebooks',
    href: '#codebooks',
  },
  history: {
    label: 'History',
    href: '#history',
  },
};

const tabs = ref(
  Object.entries(Tabs).map(([value, { label, href }]) => ({
    value,
    label,
    href,
  }))
);
const currentSubView = ref('');
const hash = window.location.hash;
if (hash) {
  // Find the tab that corresponds to the URL hash
  const matchedTab = tabs.value.find((tab) => `#${tab.value}` === hash);
  if (matchedTab) {
    currentSubView.value = matchedTab.value;
  }
}
if (!currentSubView.value) {
  currentSubView.value = tabs.value[0].value;
}

/**
 * When clicking a tab, this function will be called to change the current view
 * and load the necessary data for that view
 * @param viewName
 * @return {Promise<void>}
 */
const changeCurrentView = async (viewName) => {
  const tab = Tabs[viewName];
  if (tab && tab.load) {
    await tab.load(); // Load data for the tab if needed
  }
  currentSubView.value = viewName;
};

onMounted(() => {
  if (typeof projectId === 'undefined') {
    projectId = props.project.id;
  }
});

onBeforeUnmount(() => {
  localStorage.clear();
});
</script>
<style scoped></style>
