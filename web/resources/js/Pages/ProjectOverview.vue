<template>
  <AuthenticatedLayout :title="name" :menu="true">
    <template #menu>
      <ProjectsListMenu
          :projects="projects"
      />
    </template>
    <template #main>
      <div>
        <div class="md:px-4 md:mt-4">
          <ResponsiveTabList
              :tabs="tabs"
              :initial="currentSubView"
              @change="value => currentSubView = value"
          />
        </div>
        <div class="md:mt-8 xl:pe-6 2xl:w-3/4">
          <ProjectSummary v-if="currentSubView === 'overview'" />
          <ProjectCodebooks v-if="currentSubView === 'codebooks'" />
          <ProjectTeams
            v-if="currentSubView === 'collab'"
            :has-team="hasTeam"
            :team="team"
            :permissions="permissions"
            :available-roles="availableRoles"
            :team-owner="teamOwner"
            :project="project"
          />
          <Audit
            v-if="currentSubView === 'history'"
            :project-id="projectId"
            :audits="audits"
            context="projectPage"
          />
        </div>
      </div>
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
import ProjectCodebooks from './Projects/ProjectCodebooks.vue';
import ResponsiveTabList from '../Components/lists/ResponsiveTabList.vue';

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
const description = ref(props.project.description);
const url = window.location.pathname;
const segments = url.split('/');
let projectId = segments[2]; // Assuming project id is the third segment in URL path
const localAudits = ref([]);

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
const currentSubView = ref(tabs.value[0].value);

onMounted(() => {
  localAudits.value = props.audits;
  const hash = window.location.hash;
  if (hash) {
    // Find the tab that corresponds to the URL hash
    const matchedTab = tabs.value.find((tab) => `#${tab.value}` === hash);
    if (matchedTab) {
      currentSubView.value = matchedTab.value;
    }
  }

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
