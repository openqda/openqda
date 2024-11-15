<script setup>
import SectionTitle from './SectionTitle.vue';
</script>

<template>
  <div class="text-foreground/50">
    <SectionTitle>
      <template #title>
        <slot name="title" />
      </template>
      <template #description>
        <slot name="description" />
      </template>
    </SectionTitle>

    <div class="">
      <div class="py-1">
        <slot name="content" />
      </div>
    </div>
  </div>
</template>
