<script setup>
import {
  ChevronRightIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  EyeSlashIcon,
  BarsArrowDownIcon,
  PencilIcon,
  PlusIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/solid/index.js';
import {
  TrashIcon /*, ChatBubbleBottomCenterTextIcon */,
} from '@heroicons/vue/24/outline';
import { computed, onUnmounted, reactive, ref, watch } from 'vue';
import { cn } from '../../utils/css/cn.js';
import Button from '../../Components/interactive/Button.vue';
import { useCodes } from './useCodes.js';
import { useRange } from './useRange.js';
import { useSelections } from './selections/useSelections';
import { changeOpacity } from '../../utils/color/changeOpacity.js';
import { useRenameDialog } from '../../dialogs/useRenameDialog.js';
import { useCodingEditor } from './useCodingEditor.js';
import { useDeleteDialog } from '../../dialogs/useDeleteDialog.js';
import { useCreateDialog } from '../../dialogs/useCreateDialog.js';
import Dropdown from '../../Components/Dropdown.vue';
import DropdownLink from '../../Components/DropdownLink.vue';
import { rgbToHex } from '../../utils/color/toHex';
import { useDraggable } from 'vue-draggable-plus';
import { useDragTarget } from './useDragTarget.js';
import { debounce } from '../../utils/dom/debounce.js';
import ContrastText from '../../Components/text/ContrastText.vue';
import { useUsers } from '../../domain/teams/useUsers.js';
import ProfileImage from '../../Components/user/ProfileImage.vue';
import { asyncTimeout } from '../../utils/asyncTimeout.js';
import { attemptAsync } from '../../Components/notification/attemptAsync.js';

//------------------------------------------------------------------------
// DATA / PROPS
//------------------------------------------------------------------------
const Selections = useSelections();
const props = defineProps({
  code: Object,
  parent: Object,
  liclass: String,
  selections: Array,
  canSort: Boolean,
  hasSibling: Boolean,
});
const { getMemberBy } = useUsers();

//------------------------------------------------------------------------
// OPEN CLOSE
//------------------------------------------------------------------------
const { toggleCode, createCodeSchema, getCodebook, addCodeToParent, getCode } =
  useCodes();
const { focusSelection } = useCodingEditor();
const open = ref(false);
watch(props.code.children, (value, prevValue) => {
  if ((value?.length ?? 0) > (prevValue?.length ?? 0)) {
    open.value = true;
  }
});

//------------------------------------------------------------------------
// RANGE
//------------------------------------------------------------------------
const { range } = useRange();

//------------------------------------------------------------------------
// TEXTS (SELECTIONS)
//------------------------------------------------------------------------
const showTexts = ref(false);
const textCount = ref(0);
const textSelectionsCount = (code) => {
  let count = code.text?.length ?? 0;
  if (code.children?.length) {
    code.children.forEach((child) => {
      count += textSelectionsCount(child);
    });
  }

  return count;
};
textCount.value = computed(() => {
  return textSelectionsCount(props.code);
});
const openTexts = () => {
  showTexts.value = true;
};
const closeTexts = () => {
  showTexts.value = false;
};

//------------------------------------------------------------------------
// TOGGLE
//------------------------------------------------------------------------
const toggling = reactive({});
const handleCodeToggle = async (code) => {
  toggling[code.id] = true;
  await asyncTimeout(100);
  await attemptAsync(() => toggleCode(code));
  toggling[code.id] = false;
};

/** toggles selections / texts for this code */
const toggle = () => {
  open.value = !open.value;
  if (!open.value) {
    showTexts.value = false;
  }
};

//------------------------------------------------------------------------
// DIALOGS
//------------------------------------------------------------------------
const { open: openDeleteDialog } = useDeleteDialog();
const { open: openRenameDialog } = useRenameDialog();
const { open: openCreateDialog } = useCreateDialog();
const editCode = (target) => {
  const schema = createCodeSchema({
    title: target.name,
    description: target.description,
    color: rgbToHex(target.color),
  });
  schema.id = {
    type: String,
    label: null,
    formType: 'hidden',
    defaultValue: target.id,
  };
  delete schema.codebookId;
  openRenameDialog({ id: 'edit-code', target, schema });
};
const addSubcode = (parent) => {
  const schema = createCodeSchema({
    codebooks: [getCodebook(parent.codebook)],
    codes: [parent],
    parent,
  });
  schema.color.defaultValue = rgbToHex(parent.color);
  openCreateDialog({
    id: 'edit-code',
    schema,
    onCreated: () => (open.value = true),
  });
};
//------------------------------------------------------------------------
// DRAG DROP
//------------------------------------------------------------------------
const draggableRef = ref();
const dragEntered = ref();
const isDragging = ref(false);
const selfRef = ref();
const { dragTarget, setDragTarget, clearDrag, setDragStart, dragStarter } =
  useDragTarget();
let draggable;
const initDraggable = () => {
  if (draggable) {
    return;
  }
  draggable = useDraggable(draggableRef, props.code.children, {
    animation: 250,
    swapThreshold: 1,
    scroll: true,
    group: props.code.codebook,
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
      // const to = e.to.getAttribute('data-id');

      // clear after data retrieval
      isDragging.value = false;
      clearDrag();

      if (parentId && parentId !== codeId) {
        const moved = await addCodeToParent({ codeId, parentId });

        if (moved) {
          const index = props.code.children.findIndex((c) => c.id === codeId);
          // eslint-disable-next-line vue/no-mutating-props
          index > -1 && props.code.children.splice(index, 1);
        }
      }
    },
  });
};

const sortedTexts = computed(() => {
  if (!props.code.text?.length) return [];
  return props.code.text
    .toSorted((a, b) => a.start - b.start)
    .map((txt) => {
      const user = getMemberBy(txt.createdBy);
      txt.user = user;
      return txt;
    });
});

const applyEnter = debounce((target) => {
  if (!dragEntered.value) {
    return;
  }
  const codeId = target.getAttribute('data-code');
  setDragTarget(codeId);
}, 500);

const enter = (evt) => {
  if (evt.currentTarget.contains(evt.relatedTarget)) {
    return;
  }
  dragEntered.value = true;
  applyEnter(evt.currentTarget);
};
const leave = (evt) => {
  if (evt.currentTarget.contains(evt.relatedTarget)) {
    return;
  }
  setDragTarget(null);
  dragEntered.value = false;
};
const end = () => {
  dragEntered.value = false;
};
watch(range, (value) => {
  if (!draggable) {
    return;
  }
  if (value?.length) {
    draggable.pause();
  } else {
    draggable.resume();
  }
});

watch(open, (value) => {
  if (value) {
    initDraggable();
  }
});

onUnmounted(() => {
  if (draggable) {
    draggable.destroy();
  }
});
</script>

<template>
  <li
    :class="
      cn(
        'rounded-lg py-2 border border-transparent',
        code.parent && 'ms-2',
        open && !dragStarter && 'border-l-background',
        dragStarter === code.id && 'bg-secondary text-secondary-foreground',
        dragEntered &&
          dragTarget &&
          code.id !== dragStarter &&
          'border-secondary',
        props.liclass
      )
    "
    @dragenter="enter"
    @dragleave="leave"
    @drop="end"
    ref="selfRef"
    :data-code="code.id"
  >
    <div class="flex items-center w-auto space-x-3">
      <Button
        :title="open ? 'Hide children' : 'Show children'"
        variant="default"
        size="sm"
        class="!px-1 !py-1 !mx-0 !my-0 bg-transparent !text-foreground hover:text-background"
        @click.prevent="toggle()"
        v-if="code.children?.length"
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
        :disabled="!code.text?.length"
        @click.prevent="showTexts ? closeTexts() : openTexts()"
      >
        <BarsArrowDownIcon class="w-4 -h-4" />
        <span class="text-xs">{{
          open ? (code.text?.length ?? 0) : textCount
        }}</span>
      </Button>

      <div
        :class="
          cn(
            'flex-grow tracking-wide rounded-md px-2 py-1 text-sm text-foreground dark:text-background group hover:shadow',
            props.canSort && 'cursor-grab'
          )
        "
        :style="`background: ${changeOpacity(code.color ?? 'rgba(0,0,0,1)', 1)};`"
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
              'contrast-text'
            )
          "
        >
          <span class="line-clamp-1 flex-grow">{{ code.name }}</span>
          <span class="text-xs ms-auto font-normal hidden group-hover:inline"
            >Assign to selection {{ range.start }}:{{ range.end }}</span
          >
        </button>
        <ContrastText v-else class="line-clamp-1 flex-grow items-center"
          >{{ code.name }} {{ code.color }}</ContrastText
        >
      </div>
      <button
        class="p-0 m-0 text-foreground/80"
        @click.prevent="handleCodeToggle(code)"
        :title="
          code.active
            ? 'Code visible, click to hide'
            : 'Code hidden, click to show'
        "
      >
        <ArrowPathIcon
          v-if="toggling[code.id]"
          class="w-4 h-4 animate-spin text-foreground/50"
        />
        <EyeSlashIcon
          v-else-if="code.active === false"
          class="w-4 h-4 text-foreground/50"
        />
        <EyeIcon v-else class="w-4 h-4" />
      </button>

      <!-- code menu -->
      <Dropdown>
        <template #trigger>
          <button class="p-0 m-0">
            <EllipsisVerticalIcon class="w-4 h-4" />
          </button>
        </template>
        <template #content>
          <DropdownLink as="button" @click.prevent="editCode(code)">
            <div class="flex items-center">
              <PencilIcon class="w-4 h-4 me-2" />
              <span>Edit code</span>
            </div>
          </DropdownLink>
          <DropdownLink as="button" @click.prevent="addSubcode(code)">
            <div class="flex items-center">
              <PlusIcon class="w-4 h-4 me-2" />
              <span>Add subcode</span>
            </div>
          </DropdownLink>
          <DropdownLink
            as="button"
            @click.prevent="
              openDeleteDialog({
                target: code,
                challenge: 'name',
                message:
                  'This will also delete ALL selections in ALL sources within this project that are related to this code!',
              })
            "
          >
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
      v-if="code && textCount && showTexts"
      :style="`border-color: ${changeOpacity(code.color ?? 'rgba(0,0,0,1)', 1)};`"
      class="bg-surface border text-sm ms-8 me-1 my-1 rounded"
    >
      <ul class="divide-y divide-border">
        <li
          v-for="selection in sortedTexts"
          class="p-3 hover:bg-background/20"
          :key="selection.id"
        >
          <div
            class="w-full flex items-center justify-between tracking-wider text-xs font-semibold"
          >
            <span class="flex">
              <a
                href=""
                @click.prevent="focusSelection(selection)"
                class="font-mono hover:underline"
                >{{ selection.start }}:{{ selection.end }}</a
              >
              <ProfileImage
                v-if="selection.user"
                :src="selection.user.profile_photo_url"
                :name="selection.user.name"
                class="w-3 h-3 ms-1"
              />
            </span>
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
    <ul
      v-if="open && code.children"
      ref="draggableRef"
      class="mt-2"
      :data-id="code.id"
    >
      <CodeListItem
        v-for="child in code.children ?? []"
        :key="child.id"
        :code="child"
        :can-sort="canSort"
        :liclass="code.parent ? 'ps-1' : 'ps-1'"
        :has-siblings="code.children?.length > 1"
      />
    </ul>

    <!-- memos -->
    <!-- text selections -->
  </li>
</template>

<style scoped></style>
