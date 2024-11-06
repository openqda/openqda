<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowTopRightOnSquareIcon } from "@heroicons/vue/24/solid";

defineProps({
    external: Boolean
})
</script>

<template>
<Link v-bind="$attrs"
      class="text-foreground/80 hover:text-secondary hover:underline text-nowrap inline-flex items-center underline-offset-3"
      :rel="$props.external ?  'noopener noreferrer' : undefined">
    <slot></slot>
    <ArrowTopRightOnSquareIcon v-if="$props.external" class="ms-1 w-4 h-4" />
</Link>
</template>
