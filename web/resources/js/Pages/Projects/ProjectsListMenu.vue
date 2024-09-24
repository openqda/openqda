<script setup>
import { Routes } from '../../routes/Routes.js';
import Button from '../../Components/interactive/Button.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'

defineEmits(['create-project']);
defineProps(['projects']);
</script>

<template>
  <Button
      variant="outline-secondary"
    class="mt-5"
    title="Create new project"
      :icon="PlusIcon"
    :onclick="() => $emit('create-project')">
      New Project
  </Button>

  <ul v-if="$props.projects.length" class="mt-5">
    <li
      v-for="project in $props.projects"
      :key="project.id"
      class="group/li w-100 p-2 rounded-xl bg-transparent hover:bg-primary-l dark:hover:bg-primary-d"
    >
      <a class="flex w-100" :href="Routes.project.path(project.id)">
        <div class="flex-grow">
          <h3
            class="font-semibold text-label-l dark:text-label-d group-hover/li:text-label-d group-hover/li:dark:text-label-l"
          >
            {{ project.name }}
          </h3>
          <p
            class="py-3 text-passive-l dark:text-passive-d group-hover/li:text-passive-d group-hover/li:dark:text-passive-l"
          >
            {{ project.description }}
          </p>
        </div>
        <div class="text-center">
          <div class="text-xs text-passive-l dark:text-passive-d">Date</div>
          <div class="text-sm">
            {{ new Date(project.created_at).toLocaleDateString() }}
          </div>
        </div>
        <span></span>
      </a>
    </li>
  </ul>
</template>
