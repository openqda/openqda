<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  href: String,
  as: String,
});
</script>

<template>
  <div>
    <button
      v-if="as === 'button'"
      type="submit"
      class="block w-full px-4 py-2 text-left text-sm leading-5 text-foreground bg-surface hover:bg-primary/20 hover:text-primary-foreground focus:text-primary-foreground focus:outline-none focus:bg-primary/20 transition duration-150 ease-in-out"
    >
      <slot />
    </button>

    <a
      v-else-if="as === 'a'"
      :href="href"
      class="block px-4 py-2 text-sm leading-5 text-foreground bg-surface focus:outline-none hover:bg-primary/20  hover:text-primary-foreground focus:text-primary-foreground focus:bg-primary/20 transition duration-150 ease-in-out"
    >
      <slot />
    </a>

    <Link
      v-else
      :href="href"
      class="block px-4 py-2 text-sm leading-5 text-foreground bg-surface focus:outline-none hover:bg-primary/20 hover:text-primary-foreground focus:text-primary-foreground focus:bg-primary/20 transition duration-150 ease-in-out"
    >
      <slot />
    </Link>
  </div>
</template>
