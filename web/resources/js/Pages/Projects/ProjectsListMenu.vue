<script setup>
import { Routes } from '../../routes/Routes.js';
import Button from '../../Components/interactive/Button.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import SearchableList from '../../Components/lists/SearchableList.vue'
import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline'

defineEmits(['create-project']);
defineProps(['projects']);

const compare = (a = '', b = '') => a && a.trim().toLowerCase().includes(b)
const filterItems = (item, term) => {
    const value = term.trim().toLowerCase()
    return item && (
        compare(item.name, term) ||
        compare(item.description, term) ||
        compare(item.created_at, term) ||
        value === `id:${item.id}`
    )
}

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

    <SearchableList
        :items="$props.projects"
        :filter="filterItems"
        class="mt-5"
        liclass="group/li w-100 px-2 py-3 rounded-md bg-transparent hover:bg-secondary/20 dark:hover:bg-foreground/20"
        items="$props.projects">
        <template #item="{ id, name, description, created_at }">
            <a class="flex w-100" :href="Routes.project.path(id)">
                <div class="flex-grow max-w-1/2">
                    <h3 class="font-semibold text-foreground">
                        {{ name }}
                    </h3>
                    <p v-if="!!description" class="py-3 text-sm text-foreground/50 ">
                        {{ description }}
                    </p>
                </div>
                <div class="text-center">
                    <div class="text-xs text-foreground/50">date</div>
                    <div class="text-sm">
                        {{ new Date(created_at).toLocaleDateString() }}
                    </div>
                </div>
                <span class="text-center pl-2 text-foreground/50">
                    <EllipsisVerticalIcon class="w-4 h-4" />
                </span>
            </a>
        </template>
    </SearchableList>
</template>
