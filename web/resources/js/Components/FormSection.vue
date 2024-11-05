<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !!useSlots().actions);
</script>

<template>
  <div class="">
    <SectionTitle>
      <template #title>
        <slot name="title" />
      </template>
      <template #description>
        <slot name="description" />
      </template>
    </SectionTitle>
    <div class="mt-5 md:mt-0">
      <form @submit.prevent="$emit('submitted')">
        <slot name="form" />
        <div
          v-if="hasActions"
          class="flex items-center justify-end py-3 px-0 sm:px-6"
        >
          <slot name="actions" />
        </div>
      </form>
    </div>
  </div>
</template>
