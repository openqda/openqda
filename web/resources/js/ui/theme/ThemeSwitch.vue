<script setup>
import { Theme } from '../../theme/Theme.js';
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline';
import { onMounted, ref } from 'vue';

const current = ref(null);

defineProps({
  light: String,
  dark: String,
});

onMounted(() => {
  const currentTheme = Theme.current();
  console.debug({ currentTheme });
  current.value = currentTheme;
});

const toggleTheme = (e) => {
  e.preventDefault();
  e.stopPropagation();
  const newTheme = Theme.is(Theme.LIGHT) ? Theme.DARK : Theme.LIGHT;
  Theme.update(newTheme);
  current.value = newTheme;
};
</script>

<template>
  <span role="button" :onclick="toggleTheme">
    <SunIcon
      v-if="current === Theme.LIGHT"
      width="24"
      height="24"
      :class="['w-4 h-4', $props.light]"
    />
    <MoonIcon
      v-if="current === Theme.DARK"
      width="24"
      height="24"
      :class="['w-4 h-4', $props.dark]"
    />
  </span>
</template>
