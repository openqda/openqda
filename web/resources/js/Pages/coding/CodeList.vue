<script setup>
import { computed, onUnmounted, reactive, ref, watch } from 'vue'
import CodeListItem from './CodeListItem.vue';
import { useCodes } from './useCodes.js';
import Headline3 from '../../Components/layout/Headline3.vue';
import { cn } from '../../utils/css/cn.js';
import {
  ChevronRightIcon,
  EyeIcon,
  EyeSlashIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/solid/index.js';
import Button from '../../Components/interactive/Button.vue';
import { useDraggable } from 'vue-draggable-plus';
import { useRange } from './useRange.js';
import { asyncTimeout } from '../../utils/asyncTimeout.js';
import { useDragTarget } from './useDragTarget.js';
import { attemptAsync } from '../../Components/notification/attemptAsync.js';
import { useCodebooks } from './codebooks/useCodebooks.js';

const props = defineProps({
  codebook: Object,
  codes: Array,
});

//------------------------------------------------------------------------
// Range
//------------------------------------------------------------------------
const { range } = useRange();

//------------------------------------------------------------------------
// TOGGLE
//------------------------------------------------------------------------
const { getSortOrderBy, updateSortOrder } = useCodebooks();
const { toggleCodebook, observe, addCodeToParent, getCode } = useCodes();
const toggling = reactive({});
const handleTogglingCodebook = async (codebook) => {
  toggling[codebook.id] = true;
  await asyncTimeout(300);
  await attemptAsync(() => toggleCodebook(codebook));
  toggling[codebook.id] = false;
};

//------------------------------------------------------------------------
// TEXTS
//------------------------------------------------------------------------
const open = ref(true);

//------------------------------------------------------------------------
// SORTABLE / DRAGGABLE
//------------------------------------------------------------------------
const draggableRef = ref();
const sortable = ref(
  (props.codes ?? []).toSorted(getSortOrderBy(props.codebook))
);
const isDragging = ref(false);
const { dragTarget, setDragStart, clearDrag } = useDragTarget();
const codesCount = computed(() => {
    const countCodes = codes => {
        let count = 0
        codes.forEach(code => {
            count += 1;
            if (code.children?.length) {
                count += countCodes(code.children)
            }
        })
        return count
    }
    return countCodes(props.codes)
})
const draggable = useDraggable(draggableRef, sortable, {
  animation: 250,
  swapThreshold: window.dragThreshold ?? 0.1,
  scroll: true,
  group: props.codebook.id,
  clone: (element) => {
    if (element === undefined || element === null) {
      return element;
    }
    const elementStr = JSON.stringify(element, (key, value) => {
      if (value === element) {
        return `$element-${value.id}`;
      }
      return value;
    });
    return JSON.parse(elementStr, (key, value) => {
      if (typeof value === 'string' && value.startsWith('$el')) {
        const [, id] = value.split('$element-');
        return getCode(id);
      }
      return value;
    });
  },
  onStart(e) {
    const id = e.item.getAttribute('data-code');
    setDragStart(id);
    isDragging.value = true;
  },
  async onEnd(e) {
    const codeId = e.item.getAttribute('data-code');
    const parentId = dragTarget.value;
    const to = e.to.getAttribute('data-id');

    // clear after data retrieval
    isDragging.value = false;
    clearDrag();

    const droppedIntoList = to !== 'root';
    const droppedOnOther = parentId && parentId !== codeId;

    // code placed on another code (add as child)
    if (droppedOnOther || droppedIntoList) {
      const otherId = parentId ?? to;
      const moved = await attemptAsync(() =>
        addCodeToParent({ codeId, parentId: otherId })
      );
      if (moved) {
        const index = sortable.value.findIndex((c) => c.id === codeId);
        index > -1 && sortable.value.splice(index, 1);
      }
    }

    // otherwise we are sorting the same-level list
    else {
      const parseOrder = (list) => {
        const entries = [];
        list.forEach((code) => {
          const entry = { id: code.id };
          // add children
          if (code.children?.length) {
            entry.children = parseOrder(code.children);
          }
          entries.push(entry);
        });
        return entries;
      };

      const order = parseOrder(sortable.value);
      await attemptAsync(() =>
        updateSortOrder({ order, codebook: props.codebook })
      );
    }
  },
});

//------------------------------------------------------------------------
// OBSERVERS / WATCHERS
//------------------------------------------------------------------------
observe('store/codes', {
  added: (docs) => {
    docs.forEach((doc) => {
      if (doc.codebook === props.codebook.id && !doc.parent) {
        sortable.value.push(doc);
      }
    });
  },
  removed: (docs) => {
    docs.forEach((doc) => {
      if (doc.codebook === props.codebook.id) {
        const index = sortable.value.findIndex((d) => d.id === doc.id);
        if (index > -1) {
          sortable.value.splice(index, 1);
        }
      }
    });
  },
});

watch(range, (value) => {
  if (value?.length) {
    draggable.pause();
  } else {
    draggable.resume();
  }
});

onUnmounted(() => {
  draggable.destroy();
});
</script>

<template>
  <div class="flex items-center">
    <Button
      :title="open ? 'Close code list' : 'Open code list'"
      variant="default"
      size="sm"
      class="!px-1 !py-1 !mx-0 !my-0 bg-transparent !text-foreground hover:text-background"
      @click.prevent="open = !open"
    >
      <ChevronRightIcon
        :class="
          cn(
            'w-4 h-4 transition-all duration-300 transform',
            open && 'rotate-90'
          )
        "
      />
    </Button>
    <headline3 class="ms-4 flex-grow me-2">{{ codebook.name }}</headline3>
    <span class="text-foreground/50 text-xs mx-2"
      >{{ codesCount ?? 0 }} codes</span
    >
    <button
      class="p-0 m-0 text-foreground/80"
      @click="handleTogglingCodebook(codebook)"
      :title="
        codebook.active
          ? 'Codebook enabled, click to disable'
          : 'Codebook disabled, click to enable'
      "
    >
      <ArrowPathIcon
        v-if="toggling[codebook.id]"
        class="w-4 h-4 animate-spin text-foreground/50"
      />
      <EyeSlashIcon
        v-else-if="codebook.active === false"
        class="w-4 h-4 text-foreground/50"
      />
      <EyeIcon v-else class="w-4 h-4" />
    </button>
  </div>
  <div v-if="open">
    <p
      class="text-foreground/50"
      v-if="props.codes && props.codes.length === 0"
    >
      No codes available, please create a code and have at least one codebook
      activated.
    </p>
    <ul ref="draggableRef" data-id="root">
      <CodeListItem
        v-for="(code) in sortable"
        :isDragging="isDragging"
        :code="code"
        :key="code.id"
        :can-sort="!range?.length"
      />
    </ul>
  </div>
</template>

<style scoped></style>
