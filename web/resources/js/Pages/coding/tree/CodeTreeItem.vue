<template>
  <ul :class="cn('min-h-2', props.class)" ref="el" :data-id="props.parentId">
    <li
      v-for="el in modelValue"
      :key="el.id"
      :data-id="el.id ?? el.name"
      :class="
        cn(
          'my-2',
          el.children?.length &&
            (sorting === groupId || collapsed[el.id]) &&
            `border-l-2 rounded`
        )
      "
      :style="{ borderColor: el.color }"
    >
      <CodeTreeItemRenderer
        :code="el"
        :group-id="groupId"
        :open="collapsed[el.id]"
        :sorting="groupId === sorting"
        :show-details="props.showDetails"
        class="w-full"
        @save-code-visibility="
          (payload) => emits('save-code-visibility', payload)
        "
      />
      <Collapse
        :when="sorting === groupId || !el.children?.length || collapsed[el.id]"
        class="v-collapse"
      >
        <CodeTreeItem
          class="pl-4"
          v-model="el.children"
          :group-id="groupId"
          :parent-id="el.id"
          :show-details="props.showDetails"
          @save-code-visibility="
            (payload) => emits('save-code-visibility', payload)
          "
        />
      </Collapse>
    </li>
  </ul>
</template>
<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Collapse } from 'vue-collapsed';
import { useDraggable } from 'vue-draggable-plus';
import { cn } from '../../../utils/css/cn';
import { useCodes } from '../../../domain/codes/useCodes';
import { useCodeTree } from './useCodeTree';
import CodeTreeItemRenderer from './CodeTreeItemRenderer.vue';
import { attemptAsync } from '../../../Components/notification/attemptAsync';
import { CodeList } from '../../../domain/codes/CodeList';
import { useCodebookOrder } from '../../../domain/codebooks/useCodebookOrder';

defineOptions({
  name: 'CodeTreeItem',
});

const props = defineProps({
  modelValue: Array,
  groupId: [Number, String],
  parentId: String,
  class: String,
  showDetails: Boolean,
});

const emits = defineEmits(['update:modelValue', 'save-code-visibility']);
const list = computed({
  get: () => props.modelValue,
  set: (value) => {
    emits('update:modelValue', value);
  },
});

//------------------------------------------------------------------------
// CODES/CODEBOOKS
//------------------------------------------------------------------------
const { codebookOrderChanged } = useCodebookOrder();
const { getCode, addCodeToParent } = useCodes();

//------------------------------------------------------------------------
// DRAG DROP
//------------------------------------------------------------------------
const el = ref();
const { setDragging, collapsed, collapse, sorting } = useCodeTree();
let dragStarted = false;
const draggable = useDraggable(el, list, {
  group: String(props.groupId),
  animation: 250,
  swapThreshold: 0.5,
  scroll: true,
  immediate: false,
  scrollSensitivity: 100,
  onStart(event) {
    const codeId = event.item.getAttribute('data-id');
    setDragging(codeId);
  },
  async onEnd(event) {
    setDragging(null);
    const codeId = event.item.getAttribute('data-id');
    const to = event.to;
    const parentId = event.to.getAttribute('data-id');
    const from = event.from;
    const hierarchyChanged = to !== from;
    const code = getCode(codeId);

    if (hierarchyChanged) {
      // move code to top-level
      if (parentId === null) {
        await attemptAsync(() => addCodeToParent({ codeId, parentId: null }));

        // attach to another parent
      } else if (CodeList.dropAllowed(getCode(codeId), getCode(parentId))) {
        collapse(parentId, true);
        await attemptAsync(() => addCodeToParent({ codeId, parentId }));
      }
    }

    // always inform about code order change
    codebookOrderChanged(code.codebook);
  },
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
});

watch(sorting, (id) => {
  if (props.groupId === id) {
    if (!dragStarted) {
      draggable.start();
      dragStarted = true;
    } else {
      draggable.resume();
    }
  } else {
    draggable.pause();
  }
});
</script>
<style scoped></style>
