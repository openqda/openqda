<script setup>
// TODO deprecated

import { Link } from '@inertiajs/vue3';

import { computed, inject, onMounted, ref, watch } from 'vue';
import SettingsDropdown from '../Components/SettingsDropdown.vue';
import {
  ChartPieIcon,
  DocumentTextIcon,
  Square3Stack3DIcon,
  SquaresPlusIcon,
} from '@heroicons/vue/20/solid';
import { Project } from '../state/Project.js';

const props = defineProps({ active: String });
const usersInChannel = inject('usersInChannel');
let usersInPages = ref([]);

// TODO is this necessary
//   y: document
//   n: remove
computed(() => usersInChannel);

watch(
  usersInChannel,
  (newValue /*, oldValue */) => {
    usersInPages.value = newValue; // Correctly update the ref's value
  },
  { deep: true }
); // Use deep watch to react to nested changes

onMounted(() => {
  const projectId = Project.getId();

  if (!projectId) {
    return;
  }

  sessionStorage.setItem('projectId', projectId);

  const routes = [
    {
      name: 'Project',
      href: `/projects/${projectId}/overview`,
      current: props.active === 'project.show',
      count: 0,
      icon: Square3Stack3DIcon,
    },
    {
      name: 'Preparation',
      href: `/projects/${projectId}/preparation`,
      icon: SquaresPlusIcon,
      count: 0,
      current: props.active === 'source.index',
    },
    {
      name: 'Coding',
      href: `/projects/${projectId}/codes`,
      icon: DocumentTextIcon,
      count: 0,
      current: props.active === 'coding.show',
    },
    {
      name: 'Analysis',
      href: `/projects/${projectId}/analysis`,
      icon: ChartPieIcon,
      count: 0,
      current: props.active === 'analysis.show',
    },
  ];

  tabs.value.push(...routes);
});

const tabs = ref([]);

const selectTab = (selectedTab) => {
  tabs.value.forEach((tab) => {
    tab.current = tab.name
      .toLowerCase()
      .includes(selectedTab.name.toLowerCase());
  });
};
</script>
<style scoped>
.nav-logo {
  max-width: 5rem;
}
</style>
<template>
  <div class="border-b border-silver-100" id="navigation">
    <nav class="flex -mb-px" aria-label="Tabs">
      <span
        class="border-b-2 px-1 text-center text-sm font-medium inline-flex justify-center items-center"
      >
        <a :href="route('projects.index')" title="Return to Projects Overview">
          <img
            class="nav-logo antialiased"
            :src="$page.props.logo"
            alt="OpenQDA Logo"
          />
        </a>
      </span>
      <Link
        v-for="tab in tabs"
        :key="tab.name"
        :icon="tab.icon"
        :href="tab.href"
        @click="selectTab(tab)"
        :class="[
          tab.current
            ? 'border-cerulean-700 text-cerulean-700'
            : 'border-transparent text-silver-700 hover:border-silver-700 hover:text-silver-900',
          'w-1/4 border-b-2 py-4 px-1 text-center text-sm font-medium justify-center inline-flex flex-col items-center',
        ]"
        :aria-current="tab.current ? 'page' : undefined"
        preserve-state
      >
        <div class="flex flex-col items-center mt-2">
          <div class="flex items-center">
            <component :is="tab.icon" class="h-4 w-4"></component>
            <span class="ml-2">{{ tab.name }}</span>
          </div>
          <span
            v-if="tab.count"
            :class="[
              tab.current
                ? 'bg-cerulean-200 text-cerulean-700'
                : 'bg-silver-300 text-silver-900',
              'ml-3 hidden rounded-full py-0.5 px-2.5 text-xs font-medium md:inline-block',
            ]"
            >{{ tab.count }}</span
          >

          <!-- User icons for the current tab -->

          <div class="w-full mt-1 h-6">
            <div
              v-if="usersInPages && usersInPages.length > 0"
              class="flex justify-center -gap-2"
            >
              <span v-for="(luser, index) in usersInPages" :key="index">
                <img
                  v-if="luser.currentUrl.includes(tab.href)"
                  :src="luser.profile_photo"
                  :alt="`User ${luser.id}`"
                  class="h-6 w-6 rounded-full border-2 border-white"
                />
              </span>
            </div>
          </div>
        </div>
      </Link>

      <span class="border-b-2 py-4 px-1">
        <SettingsDropdown />
      </span>
    </nav>
  </div>
</template>
