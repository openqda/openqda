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
  size: {
    type: String,
    default: 'compact',
  },
});

let initials = props.name
  ? props.name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
  : '';
if (initials.length > 2) {
  initials = initials.slice(0, 2);
}
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
  <div v-else :title="props.name" class="cursor-default">
    <UserIcon
      v-if="size === 'icon'"
      :class="cn(`border border-inherit rounded-full w-7 h-7 p-1`, props.class)"
      :style="`background-color:hsl(${hue},67%,52%);border-color:hsl(${hue},77%,62%);fill:hsl(${hue},67%,52%);stroke:hsl(${hue},77%,62%);`"
    />
    <span
      v-else-if="size === 'compact'"
      :style="`border-color:hsl(${hue},77%,62%);color:hsl(${hue},77%,62%);`"
      :class="
        cn(
          `border bg-transparent text-xs font-normal p-0.5 rounded-full`,
          props.class
        )
      "
    >
      {{ initials }}
    </span>
    <span
      v-else
      :style="`background-color:hsl(${hue},67%,52%);border-color:hsl(${hue},77%,62%);color:hsl(${hue},77%,92%);`"
      :class="cn(`text-sm px-1 rounded`, props.class)"
    >
      <slot name="before" /> {{ props.name }} <slot name="after" />
    </span>
  </div>
</template>

<style scoped></style>
