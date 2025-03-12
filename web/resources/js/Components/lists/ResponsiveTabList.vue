<script setup lang="ts">
/*
 | This component renders a horizontal tab-list (nav tabs)
 | on md or larger screens or a native select component on
 | smaller screen sizes.
 */
import { cn } from '../../utils/css/cn';
import { ref } from 'vue';

const emit = defineEmits(['change']);
const props = defineProps({
  tabs: {
    type: Array,
  },
  initial: String,
});
const current = ref(props.initial ?? props.tabs?.[0]?.value);
const setCurrent = (value) => {
  current.value = value;
  emit('change', value);
};
</script>

<template>
  <div>
    <div class="sm:hidden">
      <label for="tabs" class="sr-only">Select a tab</label>
      <!-- Use an "onChange" listener to redirect the user to the selected tab URL. -->
      <select
        id="tabs"
        name="tabs"
        class="block w-full rounded-md focus:border-secondary focus:ring-secondary bg-surface text-foreground"
        @change="(e) => setCurrent(e.target.value)"
      >
        <option
          v-for="tab in props.tabs"
          :key="tab.value"
          :value="tab.value"
          :selected="current === tab.value"
        >
          {{ tab.label }}
        </option>
      </select>
    </div>
    <div class="hidden sm:block">
      <div class="">
        <nav
          class="flex justify-start -mb-px gap-8 tracking-wider font-semibold"
          aria-label="Tabs"
        >
          <a
            v-for="tab in props.tabs"
            :key="tab.value"
            :href="tab.href ?? ''"
            @click="
              (e) => {
                setCurrent(tab.value);
                if (!tab.href) e.preventDefault();
              }
            "
            :class="
              cn(
                'group inline-flex items-center justify-center border-b-2 py-1 px-1 text-sm',
                current === tab.value
                  ? 'border-secondary text-secondary'
                  : 'border-transparent text-foreground/60'
              )
            "
            :aria-current="current === tab.value ? 'page' : undefined"
          >
            <span>{{ tab.label }}</span>
          </a>
        </nav>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
