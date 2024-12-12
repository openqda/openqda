<template>
  <Head :title="title" />
  <div class="min-h-screen bg-background overflow-hidden">
    <slot />
  </div>
  <Footer v-if="$props.showFooter ?? shouldShowFooter" />
</template>
<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import Footer from './Footer.vue';

defineProps(['title', 'showFooter', 'menu']);

const shouldShowFooter = computed(() => {
  // Extract the path and hash from the URL
  const path = route().current();

  // Define conditions based on the path and hash
  return path !== 'coding.show' && path !== 'project.show';
});
</script>
