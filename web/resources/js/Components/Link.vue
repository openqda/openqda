<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/solid';

defineProps({
  external: Boolean,
  href: String,
});
</script>

<template>
  <a
    v-if="$props.external"
    v-bind="$attrs"
    :href="$props.href"
    target="_blank"
    rel="noopener noreferrer"
    class="text-foreground/80 hover:text-secondary hover:underline text-nowrap inline-flex items-center underline-offset-3"
  >
    <slot></slot>
    <ArrowTopRightOnSquareIcon class="ms-1 w-4 h-4" />
  </a>
  <Link
    v-else
    v-bind="$attrs"
    :href="$props.href"
    class="text-foreground/80 hover:text-secondary hover:underline text-nowrap inline-flex items-center underline-offset-3"
  >
    <slot></slot>
  </Link>
</template>
