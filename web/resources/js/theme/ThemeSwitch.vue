<script setup>
import { Switch } from '@headlessui/vue';
import { Theme } from './Theme.js';
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline';
import { onMounted, ref } from 'vue';

const current = ref(null);
const isDark = ref(false);
defineProps({
  light: String,
  dark: String,
});

onMounted(() => {
  const currentTheme = Theme.current();
  current.value = currentTheme;
  isDark.value = currentTheme === Theme.DARK;
});

const toggleTheme = (e) => {
  e.preventDefault();
  e.stopPropagation();
  const newTheme = Theme.is(Theme.LIGHT) ? Theme.DARK : Theme.LIGHT;
  Theme.update(newTheme);
  current.value = newTheme;
  isDark.value = newTheme === Theme.DARK;
};
</script>

<template>
  <Switch
    :default-checked="isDark"
    :onclick="toggleTheme"
    class="bg-foreground/10 relative inline-flex h-6 w-12 items-center rounded-full border-2 border-foreground/20"
    :v-model="isDark"
  >
    <span class="sr-only">{ current ? 'Dark mode' : 'Light mode' }</span>
    <span
      :class="isDark ? 'translate-x-6' : 'translate-x-[1.5]'"
      class="h-5 w-5 inline-flex justify-center items-center transform rounded-full bg-surface duration-200 ease-in-out"
    >
      <SunIcon
        v-if="!isDark"
        :class="['w-4 h-4 text-foreground/60', $props.light]"
      />
      <MoonIcon
        v-else
        :class="['w-4 h-4 font-semibold text-foreground/60', $props.dark]"
      />
    </span>
  </Switch>
</template>
