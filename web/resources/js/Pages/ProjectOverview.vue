<template>
  <AuthenticatedLayout :title="name" :menu="true">
    <template #menu>
      <BaseContainer>
        <ProjectsListMenu :projects="projects" />
        <div class="mt-auto">
          <Footer />
        </div>
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
        <ProjectTeams v-if="currentSubView === 'collab'" :project="project" />
        <ProjectCodebooks v-if="currentSubView === 'codebooks'" />
        <Audit
          v-if="currentSubView === 'history'"
          :project-id="projectId"
          context="projectPage"
        />
        <ProjectExport v-if="currentSubView === 'export'" :project="project" />
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
import ProjectExport from './Projects/ProjectExport.vue'
import Footer from '../Layouts/Footer.vue';

const props = defineProps([
  'project',
  'projects',
  'userCodebooks',
  'publicCodebooks',
  'hasCodebooksTab',
]);

const codebooks = ref([]);
const name = ref(props.project.name);
const url = window.location.pathname;
const segments = url.split('/');
let projectId = segments[2]; // Assuming project id is the third segment in URL path

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
  {
    value: 'export',
    label: 'Export',
    href: '#export',
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
  if (typeof projectId === 'undefined') {
    projectId = props.project.id;
  }

  codebooks.value = props.project.codebooks;
});

onBeforeUnmount(() => {
  localStorage.clear();
});
</script>
<style scoped></style>
