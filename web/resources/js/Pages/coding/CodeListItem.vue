<script setup>
import {
  ChevronRightIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  EyeSlashIcon,
  BarsArrowDownIcon,
    PencilIcon,
    ArrowTurnDownRightIcon
} from '@heroicons/vue/24/solid/index.js';
import { TrashIcon } from '@heroicons/vue/24/outline';
import { ChatBubbleBottomCenterTextIcon } from '@heroicons/vue/24/outline/index.js';
import { computed, ref } from 'vue';
import { cn } from '../../utils/css/cn.js';
import Button from '../../Components/interactive/Button.vue';
import { useCodes } from './useCodes.js';
import { useRange } from './useRange.js';
import { useSelections } from './selections/useSelections';
import { changeRGBOpacity } from '../../utils/color/changeRGBOpacity.js';
import { useRenameDialog } from '../../dialogs/useRenameDialog.js'
import { useCodingEditor } from './useCodingEditor.js';
import { useDeleteDialog } from '../../dialogs/useDeleteDialog.js'
import Dropdown from '../../Components/Dropdown.vue'
import DropdownLink from '../../Components/DropdownLink.vue'
import {useDraggable} from 'vue-draggable-plus'

const { open:openDeleteDialog } = useDeleteDialog()
const { open:openRenameDialog } = useRenameDialog()
const Selections = useSelections();
const { toggleCode, createCodeSchema } = useCodes();
const { focusSelection } = useCodingEditor();
const open = ref(false);
const props = defineProps({
  code: Object,
  parent: Object,
  isDragging:Boolean,
  liclass: String,
  selections: Array,
    canSort: Boolean
});
const { range } = useRange();
const showTexts = ref(false);
const hasTexts = computed(() => props.code.text?.length);
const toggle = () => {
  open.value = !open.value;
  if (!open.value) {
    showTexts.value = false;
  }
};
const selections = (code) => {
  let count = hasTexts.value ?? 0;

  if (code.children?.length) {
    code.children.forEach((child) => {
      count += selections(child);
    });
  }

  return count;
};

const indent = code => console.debug('indent', code)
const editCode = (target) => {
    const schema = createCodeSchema({
        title: target.name,
        description: target.description,
        color: target.color
    })
    schema.id = { type: String, formType: 'hidden', defaultValue: target.id }
    delete schema.codebookId
    openRenameDialog({ id: 'edit-code', target, schema  })
}

const draggableRef = ref(false)
const sortable = ref(props.codes ?? [])
const draggable = useDraggable(draggableRef, sortable, {
    animation: 150,
    scroll: true,
    group: `group-${props.code.id}`,
    clone: (element) => {
        if (element === undefined || element === null) return element
        const elementStr = JSON.stringify(element, (key, value) => {
            if (value === element) return '$cyclic';
            return value
        })
        return JSON.parse(elementStr)
    },
    onMove (e) {
        console.debug('drag end', e)
    },
    onStart(e) {
        console.debug('drag start', e)
    },
    onUpdate(e) {
        console.debug('drag update', e)
    },
    onEnd (e) {
        console.debug('drag end', e)
    }
})
</script>

<template>
  <li :class="cn('rounded-md py-2 border border-transparent', open && 'bg-background/20', dragenter && 'border-secondary bold', props.liclass)" :data-code="code.id">
    <div class="flex items-center w-auto space-x-3">
      <Button
        :title="open ? 'Hide children' : 'Show children'"
        variant="outline"
        size="sm"
        class="!px-1 !py-1 !mx-0 !my-0 bg-transparent"
        @click.prevent="toggle()"
        v-if="code.children.length"
      >
        <ChevronRightIcon :class="cn('w-4 h-4', open && 'rotate-90')" />
      </Button>
      <span class="w-4 h-4 p-1 m-1" v-else></span>
      <Button
        :title="showTexts ? 'Hide selections list' : 'Show selections list'"
        variant="ghost"
        size="sm"
        :class="
          cn(
            '!px-1 !py-1 !my-0 w-8 text-xs hover:text-secondary',
            showTexts && 'text-secondary'
          )
        "
        :disabled="!hasTexts"
        @click.prevent="showTexts = !showTexts"
      >
        <BarsArrowDownIcon class="w-4 -h-4" />
        <span class="text-xs">{{
          open ? (hasTexts ?? 0) : selections(code)
        }}</span>
      </Button>
      <div
        :class="cn('flex-grow tracking-wide rounded-md px-2 py-1 text-sm text-foreground dark:text-background group hover:shadow', props.canSort && 'cursor-grab')"
        :style="`background: ${changeRGBOpacity(code.color, 1)};`"
      >
        <button
          v-if="range?.length"
          @click.prevent="Selections.select({ code, parent })"
          :disabled="!code.active"
          :title="`Assign ${code.name} to selection ${range.start}:${range.end}`"
          :class="
            cn(
              'w-full h-full text-left flex',
              code.active
                ? 'hover:font-semibold'
                : 'cursor-not-allowed text-opacity-20',
            )
          "
        >
          <span class="line-clamp-1">{{ code.name }}</span>
          <span class="text-xs ms-auto font-normal hidden group-hover:inline"
            >Assign to selection {{ range.start }}:{{ range.end }}</span
          >
        </button>
        <div v-else class="w-full group flex">
            <span class="line-clamp-1 flex-grow items-center">{{ code.name }}</span>
            <div v-show="isDragging" class="bg-background rounded px-3 py-1 h-full text-foreground/60 hover:text-foreground">
                <ArrowTurnDownRightIcon class="w-4 h-4" />
            </div>
        </div>
      </div>
      <button
        class="p-0 m-0 text-foreground/80"
        @click.prevent="toggleCode(code)"
        :title="
          code.active
            ? 'Code visible, click to hide'
            : 'Code hidden, click to show'
        "
      >
        <EyeSlashIcon
          v-if="code.active === false"
          class="w-4 h-4 text-foreground/50"
        />
        <EyeIcon v-else class="w-4 h-4" />
      </button>
      <!-- memo icon -->
      <button class="p-0 m-0 flex" v-if="false">
        <ChatBubbleBottomCenterTextIcon class="w-4 h-4" />
        <span class="text-xs">2</span>
      </button>

        <!-- code menu -->
        <Dropdown>
            <template #trigger>
                <button class="p-0 m-0">
                    <EllipsisVerticalIcon class="w-4 h-4" />
                </button>
            </template>
            <template #content>
                <DropdownLink as="button"
                              @click.prevent="editCode(code)"
                >
                    <div class="flex items-center">
                        <PencilIcon class="w-4 h-4 me-2" />
                        <span>Edit code</span>
                    </div>
                </DropdownLink>
                <DropdownLink as="button" @click.prevent="openDeleteDialog({ target: code, challenge: 'name', message: 'This will also delete ALL selections in ALL sources within this project that are related to this code!' })">
                    <div class="flex">
                        <TrashIcon class="w-4 h-4 me-2 text-destructive" />
                        <span>Delete this code</span>
                    </div>
                </DropdownLink>
            </template>
        </Dropdown>
    </div>

      <!-- TEXT Selections -->
    <div
      v-if="hasTexts && showTexts"
      :style="`border-color: ${changeRGBOpacity(code.color, 1)};`"
      class="bg-surface border text-sm ms-8 me-1 my-1 rounded"
    >
      <ul class="divide-y divide-border">
        <li
          v-for="selection in code.text.toSorted((a, b) => a.start - b.start)"
          class="p-3 hover:bg-background/20"
        >
          <div
            class="w-full flex items-center justify-between tracking-wider text-xs font-semibold"
          >
            <a
              href=""
              @click.prevent="focusSelection(selection)"
              class="font-mono hover:underline"
              >{{ selection.start }}:{{ selection.end }}</a
            >
            <button
              class="p-2 me-1"
              @click="Selections.deleteSelection(selection)"
              title="Delete this selection"
            >
              <TrashIcon class="w-4 h-4 hover:text-destructive" />
            </button>
          </div>
          <p class="cursor-text">{{ selection.text }}</p>
        </li>
      </ul>
    </div>

    <!-- children -->
    <ul ref="draggableRef" v-if="code.children">
      <CodeListItem
        v-for="child in (code.children ?? [])"
        :key="child.id"
        :code="child"
        :can-sort="canSort"
        liclass="ps-3"
      />
    </ul>

    <!-- memos -->
    <!-- text selections -->
  </li>
</template>

<style scoped></style>
