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
          @change="(value) => (currentSubView = value)"
        />
        <ProjectSummary v-if="currentSubView === 'overview'" />
        <ProjectTeams
          v-if="currentSubView === 'collab'"
          :has-team="hasTeam"
          :team="team"
          :permissions="permissions"
          :available-roles="availableRoles"
          :team-owner="teamOwner"
          :project="project"
        />
        <ProjectCodebooks v-if="currentSubView === 'codebooks'" />
        <Audit
          v-if="currentSubView === 'history'"
          :project-id="projectId"
          :audits="audits"
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
import { onBeforeUnmount, onMounted, ref, watch, provide } from 'vue';
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

const props = defineProps([
  'project',
  'projects',
  'teamMembers',
  'hasTeam',
  'availableRoles',
  'permissions',
  'team',
  'audits',
  'userCodebooks',
  'publicCodebooks',
  'hasCodebooksTab',
  'teamOwner',
]);

const codebooks = ref([]);
const name = ref(props.project.name);
const url = window.location.pathname;
const segments = url.split('/');
let projectId = segments[2]; // Assuming project id is the third segment in URL path
const localAudits = ref([]);
const { createProject, createSchema, open } = useProjects();
const createProjectSchema = ref(null);

provide('project', props.project);
provide('userCodebooks', props.userCodebooks);
provide('publicCodebooks', props.publicCodebooks);

const tabs = ref([
  {
    value: 'overview',
    label: 'Overview',
    href: '#overview',
  },

  {
    value: 'collab',
    label: 'Collaboration',
    href: '#collab',
  },
  {
    value: 'codebooks',
    label: 'Codebooks',
    href: '#codebooks',
  },
  {
    value: 'history',
    label: 'History',
    href: '#history',
  },
]);
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

onMounted(() => {
  localAudits.value = props.audits;

  if (typeof projectId === 'undefined') {
    projectId = props.project.id;
  }

  codebooks.value = props.project.codebooks;
});

// Watch for changes in props.audits and update localAudits accordingly
watch(
  () => props.audits,
  (newVal) => {
    localAudits.value = newVal;
  }
);

onBeforeUnmount(() => {
  localStorage.clear();
});
</script>
<style scoped></style>
