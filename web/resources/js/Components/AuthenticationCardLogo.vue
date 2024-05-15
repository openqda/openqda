<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <Link class="w-1/6" :href="'/'">
    <img :src="$page.props.logo" alt="mecode" />
  </Link>
</template>
