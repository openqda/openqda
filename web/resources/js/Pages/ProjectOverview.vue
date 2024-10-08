<template>
  <AuthenticatedLayout :title="name" :menu="true">
    <template #menu>
      <ProjectsListMenu
        :projects="projects"
        @selected="projectSelected"
        @create-project="() => projectForm(true)"
      />
    </template>
    <template #main>
      <div>
        <div class="px-4 md:mt-4">
          <div class="sm:hidden">
            <label for="tabs" class="sr-only">Select a tab</label>
            <!-- Use an "onChange" listener to redirect the user to the selected tab URL. -->
            <select
              id="tabs"
              name="tabs"
              class="block w-full rounded-md focus:border-primary focus:ring-primary"
            >
              <option
                v-for="tab in tabs"
                :key="tab.name"
                :selected="tab.current"
              >
                {{ tab.name }}
              </option>
            </select>
          </div>
          <div class="hidden sm:block">
            <div class="">
              <nav
                class="flex justify-start -mb-px space-x-8 tracking-wider font-semibold"
                aria-label="Tabs"
              >
                <a
                  v-for="tab in tabs"
                  :key="tab.key"
                  :href="tab.href"
                  @click="toggleSubView(tab.key, $event)"
                  :class="
                    cn(
                      'group inline-flex items-center justify-center border-b-2 py-1 px-1 text-sm',
                      currentSubView === tab.key
                        ? 'border-secondary text-secondary'
                        : 'border-transparent text-foreground/60'
                    )
                  "
                  :aria-current="tab.current ? 'page' : undefined"
                >
                  <span>{{ tab.name }}</span>
                </a>
              </nav>
            </div>
          </div>
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
import { computed, onBeforeUnmount, onMounted, ref, watch, provide } from 'vue';
import {
  ClockIcon,
  PresentationChartLineIcon,
  RectangleStackIcon,
} from '@heroicons/vue/20/solid';
import { UsersIcon } from '@heroicons/vue/24/outline';
import Audit from '../Components/global/Audit.vue';
import ProjectTeams from './Teams/ProjectTeams.vue';
import ProjectsListMenu from './Projects/ProjectsListMenu.vue';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout.vue';
import ProjectSummary from './Projects/ProjectSummary.vue';
import { cn } from '../utils/css/cn.js';
import ProjectCodebooks from './Projects/ProjectCodebooks.vue'


const codebooks = ref([]);

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


const name = ref(props.project.name);
const description = ref(props.project.description);
const url = window.location.pathname;
const segments = url.split('/');
let projectId = segments[2]; // Assuming project id is the third segment in URL path
const localAudits = ref([]);

provide('project', props.project);
provide('userCodebooks', props.userCodebooks);
provide('publicCodebooks', props.publicCodebooks);


onMounted(() => {
  localAudits.value = props.audits;
  const hash = window.location.hash;
  if (hash) {
    // Find the tab that corresponds to the URL hash
    const matchedTab = tabs.value.find((tab) => `#${tab.key}` === hash);
    if (matchedTab) {
      currentSubView.value = matchedTab.key;
      // Update the 'current' property of all tabs
      tabs.value.forEach((tab) => {
        tab.current = tab.key === matchedTab.key;
      });
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



const currentSubView = ref('overview');
const tabs = ref([
  {
    key: 'overview',
    name: 'Overview',
    href: '#overview',
    icon: PresentationChartLineIcon,
    current: true,
  },

  {
    key: 'collab',
    name: 'Collaboration',
    href: '#collab',
    icon: UsersIcon,
    current: false,
  },
  {
    key: 'codebooks',
    name: 'Codebooks',
    href: '#codebooks',
    icon: RectangleStackIcon,
    current: false,
  },
  {
    key: 'history',
    name: 'History',
    href: '#history',
    icon: ClockIcon,
    current: false,
  },
]);

onBeforeUnmount(() => {
  localStorage.clear();
});

function toggleSubView(key) {
  currentSubView.value = key;
}
</script>
<style scoped></style>
