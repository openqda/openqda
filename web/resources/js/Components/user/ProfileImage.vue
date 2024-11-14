<script setup>
import { ref } from 'vue';
import { UserIcon } from '@heroicons/vue/24/outline/index.js';
import { cn } from '../../utils/css/cn.js';
import { getUserColor } from './getUserColor.js';

const props = defineProps({
  src: {
    type: String,
  },
  name: {
    type: String,
  },
  email: {
    type: String,
  },
  alt: {
    type: String,
    required: false,
  },
  class: {
    type: String,
    required: false,
  },
});

const char = (props.name ?? '').charAt(0).toUpperCase();
const showImage = ref(!!props.src);
const hue = ref(getUserColor(props.email) || getUserColor(props.name));
</script>

<template>
  <img
    v-if="showImage"
    :class="
      cn(
        'object-cover w-7 h-7 rounded-full overflow-hidden leading-loose',
        props.class
      )
    "
    :src="props.src"
    :alt="alt"
    :title="props.name"
    @error="showImage = false"
  />
  <a href="" v-else :title="props.name">
    <UserIcon
      :class="cn(`border border-inherit rounded-full w-7 h-7 p-1`, props.class)"
      :style="`background-color:hsl(${hue},67%,52%);border-color:hsl(${hue},77%,62%);fill:hsl(${hue},67%,52%);stroke:hsl(${hue},77%,62%);`"
    />
  </a>
</template>

<style scoped></style>
