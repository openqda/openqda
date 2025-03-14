<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { cn } from '../../../utils/css/cn';
import { useCodeTree } from './useCodeTree';
import { useCodes } from '../../../domain/codes/useCodes';
import {
  ArrowPathIcon,
  BarsArrowDownIcon,
  ChevronRightIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  EyeSlashIcon,
  PencilIcon,
  PlusIcon,
} from '@heroicons/vue/24/solid';
import Button from '../../../Components/interactive/Button.vue';
import { TrashIcon } from '@heroicons/vue/24/outline';
import DropdownLink from '../../../Components/DropdownLink.vue';
import Dropdown from '../../../Components/Dropdown.vue';
import { changeOpacity } from '../../../utils/color/changeOpacity';
import { useUsers } from '../../../domain/teams/useUsers';
import SelectionList from './SelectionList.vue';
import { Collapse } from 'vue-collapsed';
import { asyncTimeout } from '../../../utils/asyncTimeout';
import { attemptAsync } from '../../../Components/notification/attemptAsync';
import { useDeleteDialog } from '../../../dialogs/useDeleteDialog';
import { useRenameDialog } from '../../../dialogs/useRenameDialog';
import { useCreateDialog } from '../../../dialogs/useCreateDialog';
import ContrastText from '../../../Components/text/ContrastText.vue';
import { useRange } from '../useRange';
import { rgbToHex } from '../../../utils/color/toHex';
import { useSelections } from '../selections/useSelections';

const { toggleCode, createCodeSchema, getCodebook } = useCodes();
const { collapsed, toggleCollapse } = useCodeTree();
const { getMemberBy } = useUsers();
const Selections = useSelections();

const props = defineProps({
  code: Object,
  class: String,
  sorting: Boolean,
  search: String,
});

//------------------------------------------------------------------------
// Collapse
//------------------------------------------------------------------------
const open = computed(() => props.sorting || collapsed.value[props.code.id]);
const toggle = () => {
  const newState = toggleCollapse(props.code.id);
  // if collapse closed then also close
  // text segments
  if (!newState) {
    closeTexts();
  }
};

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
const sortedTexts = computed(() => {
  if (!props.code.text?.length) return [];
  return props.code.text
    .toSorted((a, b) => a.start - b.start)
    .map((txt) => {
      txt.user = getMemberBy(txt.createdBy);
      return txt;
    });
});

//------------------------------------------------------------------------
// VISIBILITY
//------------------------------------------------------------------------
const toggling = reactive({});
const handleCodeToggle = async (code) => {
  toggling[code.id] = true;
  await asyncTimeout(100);
  await attemptAsync(() => toggleCode(code));
  toggling[code.id] = false;
};

//------------------------------------------------------------------------
// SEARCH
//------------------------------------------------------------------------
const hasSearch = computed(() => props.search?.length > 2);
const searchMatches = computed(() => {
  if (!hasSearch.value) return false;
  const terms = props.search.split('|');
  return terms.some((term) => props.code.name.includes(term));
});

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
// Range
//------------------------------------------------------------------------
const { range } = useRange();
</script>

<template>
  <div class="w-full">
    <div class="flex items-center w-auto">
      <!-- collapse button -->
      <Button
        v-if="code.children?.length"
        :title="open ? 'Hide children' : 'Show children'"
        variant="default"
        size="sm"
        :disabled="sorting || hasSearch"
        class="bg-transparent !text-foreground hover:text-background w-4 !p-0 rounded"
        @click="toggle()"
      >
        <ChevronRightIcon
          :class="
            cn(
              'w-4 h-4 transition-all duration-300 transform',
              (hasSearch || open) && 'rotate-90'
            )
          "
        />
      </Button>
      <span class="w-4 h-4" v-else></span>

      <!-- code name -->
      <div
        :class="
          cn(
            'w-full tracking-wide rounded-md px-2 py-1 text-sm text-foreground dark:text-background group hover:shadow-sm',
            sorting && 'cursor-grab'
          )
        "
        :style="`background: ${changeOpacity(code.color ?? 'rgba(0,0,0,1)', !hasSearch || searchMatches ? 1 : 0.2)};`"
      >
        <button
          v-if="!sorting && range?.length"
          @click.prevent="Selections.select({ code })"
          :title="`Assign ${code.name} to selection ${range.start}:${range.end}`"
          :class="
            cn(
              'w-full h-full text-left flex',
              code.active
                ? 'hover:font-semibold'
                : 'cursor-not-allowed text-foreground/20',
              'contrast-text'
            )
          "
        >
          <span class="line-clamp-1 grow">{{ code.name }}</span>
          <span class="text-xs ms-auto font-normal hidden group-hover:inline"
            >Assign to {{ range.start }}:{{ range.end }}</span
          >
        </button>
        <ContrastText v-else class="line-clamp-1 grow items-center"
          >{{ code.name }}
        </ContrastText>
      </div>

      <div class="flex justify-between items-center gap-2">
        <!-- show texts -->
        <Button
          :title="showTexts ? 'Hide selections list' : 'Show selections list'"
          variant="ghost"
          size="sm"
          :class="
            cn(
              'px-1! py-1! my-0! w-8 text-xs hover:text-secondary',
              showTexts && 'text-secondary'
            )
          "
          :disabled="!code.text?.length"
          @click.prevent="showTexts ? closeTexts() : openTexts()"
        >
          <BarsArrowDownIcon class="w-4 -h-4" />
          <span class="text-xs">{{
            hasSearch || open ? (code.text?.length ?? 0) : textCount
          }}</span>
        </Button>

        <!-- visibility -->
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
    </div>

    <!-- TEXT Selections -->
    <Collapse :when="code && textCount && showTexts">
      <div
        :style="`border-color: ${changeOpacity(code.color ?? 'rgba(0,0,0,1)', 1)};`"
        class="bg-surface border text-sm ms-4 me-16 my-1 rounded"
      >
        <SelectionList
          :texts="sortedTexts"
          :color="code.color ?? 'rgba(0,0,0,1)'"
        />
      </div>
    </Collapse>
  </div>
</template>

<style scoped></style>
