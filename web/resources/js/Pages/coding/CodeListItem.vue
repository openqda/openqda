<script setup>
import {
  ChevronRightIcon,
  EllipsisVerticalIcon,
  EyeIcon,
    EyeSlashIcon
} from '@heroicons/vue/24/solid/index.js';
import { ref } from 'vue';
import { cn } from '../../utils/css/cn.js';
import Button from '../../Components/interactive/Button.vue';
import { useCodes } from './useCodes.js'
import { useRange } from './useRange.js'
import { useSelections } from './selections/useSelections'

const { select } = useSelections()
const { toggleCode } = useCodes()
const open = ref(false);
const props = defineProps({
  code: Object,
  parent: Object,
  liclass: String,
  selections: Array,
});

const { range } = useRange()
const toggle = () => (open.value = !open.value);
const avtivateCode = () => toggle();
const selections = (code) => {
    let count = (code.text?.length ?? 0)

    if (code.children?.length) {
        code.children.forEach(child => {
            count += selections(child)
        })
    }

    return count
}
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
        >{{ open ? (code.text?.length ?? 0) : selections(code) }}</span
      >
      <div class="flex-grow px-1">
        <button
            v-if="range?.length"
            @click="select({ code, parent })"
            :disabled="!code.active"
            :title="`Assign ${code.name} to selection ${range.start}:${range.end}`"
            :class="cn('w-full text-left', code.active ? 'hover:bg-secondary/20' : 'cursor-not-allowed text-opacity-20')"
        >
          <span>{{ code.name }}</span>
        </button>
          <span v-else class="w-full">{{ code.name }}</span>
      </div>
        <Button variant="ghost" class="p-0 m-0 text-foreground/80" @click="toggleCode(code)">
            <EyeSlashIcon v-if="code.active === false" class="w-3 h-3 text-foreground/50" />
            <EyeIcon v-else class="w-3 h-3" />
        </Button>
      <Button variant="ghost" class="p-0 m-0">
        <EllipsisVerticalIcon class="w-3 h-3" />
      </Button>
    </div>
    <ul v-if="code.children?.length && open">
      <CodeListItem
        v-for="child in code.children"
        :code="child"
        :parent="code"
        liclass="ps-3"
      />
    </ul>
  </li>
</template>

<style scoped></style>
