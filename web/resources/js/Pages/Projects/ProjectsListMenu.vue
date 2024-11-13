<script setup>
import { Routes } from '../../routes/Routes.js';
import Button from '../../Components/interactive/Button.vue';
import {
  PlusIcon,
  ChevronDownIcon,
  UsersIcon,
  KeyIcon,
} from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';
import { useProjects } from './useProjects.js';
import Dropdown from '../../Components/Dropdown.vue';
import DropdownLink from '../../Components/DropdownLink.vue';
import InputField from '../../form/InputField.vue';
import { cn } from '../../utils/css/cn.js';
import Headline3 from '../../Components/layout/Headline3.vue';
import { onMounted, ref } from 'vue'
import { isInViewport } from '../../utils/dom/isInViewport.js'

defineEmits(['create-project']);

const {
  projects,
  currentProject,
  sortOptions,
  sortBy,
  updateSorter,
  searchTerm,
  initSearch
} = useProjects();

const scrollRefs = ref()
const focusCurrent = () => {
    if (currentProject?.name && scrollRefs.value) {
        const li = scrollRefs.value.find(item => {
            return item.getAttribute('data-id') == (currentProject.id)
        })

        if (!isInViewport(li)) {
            setTimeout(() => {
               li.scrollIntoView({ behavior: "instant", block: "start", inline: "start" });
            }, 100)
        }
    }
}

initSearch()

onMounted(() => {
    try {
        focusCurrent()
    } catch {}
})
</script>

<template>
  <div class="flex justify-between items-baseline">
    <Button
      variant="outline-secondary"
      title="Create new project"
      :icon="PlusIcon"
      :onclick="() => $emit('create-project')"
    >
      New Project
    </Button>

    <Dropdown v-if="projects?.length">
      <template #trigger>
        <a
          href=""
          class="hover:underline text-foreground text-sm flex items-center"
          @click.prevent
        >
          <ChevronDownIcon class="w-4 h-4 me-2" />
          {{ sortBy.label }}
        </a>
      </template>
      <template #content>
        <DropdownLink
          as="button"
          v-for="(sorter, index) in sortOptions"
          :key="sorter.id"
          :data-id="index"
          :class="
            cn(
              'text-nowrap',
              index === sortBy.id && 'bg-secondary text-secondary-foreground'
            )
          "
          @click.prevent="updateSorter(sorter)"
        >
          {{ sorter.label }}
        </DropdownLink>
      </template>
    </Dropdown>
  </div>

  <InputField type="search" placeholder="Search..." v-model="searchTerm" />
    <div v-if="!projects?.length" class="text-sm text-foreground/50">
        <p v-if="searchTerm?.length">
            No projects matched for your search.
        </p>
        <p v-else>
            You have not created any project. Best, you create one now.
        </p>
    </div>

  <ul class="pb-12" v-if="projects?.length">
    <li
      v-for="entry in projects"
      ref="scrollRefs"
      :data-id="entry.id"
      :class="
        cn(
          'group/li w-full px-2 py-3 rounded-md bg-transparent hover:bg-secondary/20 dark:hover:bg-foreground/20 text-foreground',
          currentProject?.id === entry.id && 'bg-secondary/20'
        )
      "
    >
      <Link
        class="flex items-center space-x-4"
        :href="Routes.project.path(entry.id)"
        :title="
          currentProject?.id === entry.id
            ? 'Current selected project'
            : `Open and edit ${entry.name}`
        "
      >
        <div class="flex-1">
          <Headline3 class="line-clamp-1">{{ entry.name }}</Headline3>
          <p class="py-1 text-sm text-foreground/50 line-clamp-2">
            {{ entry.description }}
          </p>
        </div>
        <span class="self-center" title="Collaborative">
          <UsersIcon v-if="entry.isCollaborative" class="w-4 h-4" />
        </span>
        <span class="self-center" title="I own this project">
          <KeyIcon v-if="entry.isOwner" class="w-4 h-4" />
        </span>
        <div class="text-center w-1/6">
          <div class="text-xs text-foreground/50">date</div>
          <div class="text-sm">
            {{ new Date(entry.updated_at).toLocaleDateString() }}
          </div>
        </div>
      </Link>
    </li>
  </ul>
</template>
