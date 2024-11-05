<script setup lang="ts">
import { useCodes } from './useCodes';
import { Switch } from '@headlessui/vue';
import { cn } from '../../utils/css/cn';

const { codebooks, toggleCodebook } = useCodes();
</script>

<template>
  <ul class="space-y-6">
    <li
      v-for="(codebook) in codebooks"
      :key="codebook.id"
      class="flex justify-between hover:bg-foreground/10 py-4 px-2 rounded-md"
    >
      <span>{{ codebook.name }}</span>
      <Switch
        @click="toggleCodebook(codebook)"
        :class="
          cn(
            codebook.active ? 'bg-secondary' : 'bg-background',
            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-background transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2'
          )
        "
      >
        <span class="sr-only">Use setting</span>
        <span
          aria-hidden="true"
          :class="
            cn(
              codebook.active ? 'translate-x-5' : 'translate-x-0',
              'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-input shadow ring-0 transition duration-200 ease-in-out'
            )
          "
        />
      </Switch>
    </li>
  </ul>
</template>

<style scoped></style>
