<script setup>
import {onMounted, onUnmounted, ref, watch} from 'vue'
import CodeListItem from './CodeListItem.vue';
import { useCodes } from './useCodes.js';
import Headline3 from '../../Components/layout/Headline3.vue';
import { cn } from '../../utils/css/cn.js';
import {
  ChevronRightIcon,
  EyeIcon,
  EyeSlashIcon,
} from '@heroicons/vue/24/solid/index.js';
import Button from '../../Components/interactive/Button.vue';
import { useDraggable } from 'vue-draggable-plus'
import { useRange } from './useRange.js'


const { range } = useRange()
const { toggleCodebook, observe } = useCodes();
const props = defineProps({
  codebook: Object,
    codes: Array,
});

const open = ref(true);
const draggableRef = ref()
const sortable = ref(props.codes ?? [])
const draggable = useDraggable(draggableRef, sortable, {
    animation: 150,
    clone: (element) => {
        if (element === undefined || element === null) return element
        const elementStr = JSON.stringify(element, (key, value) => {
            if (value === element) return '$cyclic';
            return value
        })
        return JSON.parse(elementStr)
    },
    onStart(e) {
    },
    onUpdate(e) {
        console.debug(e)
    }
})

observe('store/codes', {
    added: docs => {
        docs.forEach(doc => {
            if (doc.codebook === props.codebook.id) {
                sortable.value.push(doc)
            }
        })
    },
    removed: docs => {
        docs.forEach(doc => {
            if (doc.codebook === props.codebook.id) {
                const index = sortable.value.findIndex(d => d.id === doc.id)
                if (index > -1) {
                    sortable.value.splice(index, 1)
                }
            }
        })
    }
})

watch(range, value => {
    if (value?.length) {
      draggable.pause()
    } else {
      draggable.resume()
    }
})

onMounted(() => {
    watch(() => props.codes, ({ value }) => {
        console.debug('codes changed', value)
    })
})
onUnmounted(() => {
    draggable.destroy()
})
</script>

<template>
  <div class="flex items-center">
    <Button
      :title="open ? 'Close code list' : 'Open code list'"
      variant="outline"
      size="sm"
      class="!px-1 !py-1 !mx-0 !my-0 bg-transparent"
      @click.prevent="open = !open"
    >
      <ChevronRightIcon :class="cn('w-4 h-4', open && 'rotate-90')" />
    </Button>
    <headline3 class="ms-4 flex-grow me-2">{{ codebook.name }}</headline3>
      <span class="text-foreground/50 text-xs mx-2">{{ props.codes?.length ?? 0}} codes</span>
    <button
      class="p-0 m-0 text-foreground/80"
      @click="toggleCodebook(codebook)"
      :title="
        codebook.active
          ? 'Codebook enabled, click to disable'
          : 'Codebook disabled, click to enable'
      "
    >
      <EyeSlashIcon
        v-if="codebook.active === false"
        class="w-4 h-4 text-foreground/50"
      />
      <EyeIcon v-else class="w-4 h-4" />
    </button>
  </div>
  <div v-if="open">
    <p class="text-foreground/50" v-if="props.codes && props.codes.length === 0">
      No codes available, please activate at least one codebook.
    </p>
    <ul ref="draggableRef">
      <CodeListItem v-for="code in sortable" :code="code" :key="code.id" :can-sort="!range?.length" />
    </ul>
  </div>
</template>

<style scoped></style>
