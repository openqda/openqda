<script setup lang="ts">
import { ref } from 'vue';
import { cn } from '../../utils/css/cn';
import Button from '../interactive/Button.vue';

const selectedTab = ref();
const emit = defineEmits(['selected']);
const props = defineProps(['options', 'class']);

function changeTab(index, option) {
  selectedTab.value = index;
  emit('selected', { index, ...props.options[index] });
  option.click?.();
}
const changeSelect = (e) => {
  const index = e.target.selectedOptions[0].dataset.index;
  const option = props.options[index];
  changeTab(Number(index), option);
};
</script>

<template>
  <div :class="props.class">
    <div class="sm:hidden">
      <label for="tabs" class="sr-only">Select a tab</label>
      <!-- Use an "onChange" listener to redirect the user to the selected tab URL. -->
      <select
        id="tabs"
        name="tabs"
        class="block w-full rounded-md border-border focus:border-secondary focus:ring-secondary"
        @change="changeSelect"
      >
        <option selected disabled>(Select one)</option>
        <option
          v-for="(option, index) in props.options"
          :data-index="index"
          :key="option.next"
          :selected="selectedTab === index"
        >
          {{ option.label }}
        </option>
      </select>
    </div>
    <div class="hidden sm:block">
      <nav class="flex gap-4 w-full" aria-label="Tabs">
        <Button
          v-for="(option, index) in props.options"
          :key="option.next"
          href=""
          @click.prevent="() => changeTab(index, option)"
          :class="
            cn(
              selectedTab === index
                ? 'bg-secondary text-secondary-foreground'
                : 'text-foreground/60 hover:text-foreground bg-background',
              'rounded-md px-3 py-2 text-sm font-medium grow text-center',
              option.class
            )
          "
          :aria-current="selectedTab === index ? 'page' : undefined"
          >{{ option.label }}</Button
        >
      </nav>
    </div>
  </div>
</template>

<style scoped></style>
