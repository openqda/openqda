<script setup>
defineProps({
    src: {
        type: String
    },
    alt: {
        type: String,
        required: false
    }
})
</script>

<template>
  <img
    class="object-cover w-full h-full rounded-full border border-silver-300 leading-loose"
    :src="src"
    :alt="alt"
  />
</template>

<style scoped></style>
