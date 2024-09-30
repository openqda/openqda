<script setup>
import { ref, onMounted } from 'vue'
import { UserIcon } from '@heroicons/vue/24/outline/index.js'

const props = defineProps({
  src: {
    type: String,
  },
    name: {
      type: String
    },
  alt: {
    type: String,
    required: false,
  },
});

const imageLoaded = ref(false)
const char = props.name.charAt(0).toUpperCase()
</script>

<template>
  <img
      v-if="imageLoaded"
    class="object-cover w-full h-full rounded-full overflow-hidden leading-loose"
    :alt="alt"
  />
    <div class="border border-inherit rounded-full text-inherit w-5 h-5">
        {{char}}
    </div>
</template>

<style scoped></style>
