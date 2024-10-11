<script setup>
import {
  ChevronRightIcon,
  EllipsisVerticalIcon,
} from '@heroicons/vue/24/solid/index.js';
import { ref } from 'vue';
import { cn } from '../../utils/css/cn.js';
import Button from '../../Components/interactive/Button.vue';

const open = ref(false);
const props = defineProps({
  code: Object,
  liclass: String,
  selections: Array,
});

const toggle = () => (open.value = !open.value);
const avtivateCode = () => toggle();
</script>

<template>
  <li :class="cn('hover:bg-secondary-100', props.liclass)">
    <div class="flex items-center w-auto">
      <button
        title="Toggle children"
        class="p-0 m-0 bg-transparent"
        @click.prevent="toggle"
        v-if="code.children.length"
      >
        <ChevronRightIcon :class="cn('w-4 h-4', open && 'rotate-90')" />
      </button>
      <span class="w-4 h-4" v-else></span>
      <span
        class="w-5 rounded mx-1 p-1 text-center text-sm"
        :style="`background: ${code.color};`"
        >{{ props.selections?.length ?? 0 }}</span
      >
      <div class="flex-grow px-1">
        <button @click="avtivateCode">
          <span>{{ code.name }}</span>
        </button>
      </div>
      <Button variant="ghost" class="p-0 m-0">
        <EllipsisVerticalIcon class="w-3 h-3" />
      </Button>
    </div>
    <ul v-if="code.children?.length && open">
      <CodeListItem
        v-for="child in code.children"
        :code="child"
        liclass="ps-3"
      />
    </ul>
  </li>
</template>

<style scoped></style>
