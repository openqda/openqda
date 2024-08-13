<template>
  <div
    class="absolute z-50 right-0 w-44 mt-2 bg-white rounded shadow-lg ring-1 ring-black ring-opacity-5"
    role="menu"
    aria-orientation="vertical"
    aria-labelledby="options-menu"
  >
    <button
      class="w-full flex items-center justify-start py-2 px-4 hover:bg-gray-200 rounded"
      role="menuitem"
    >
      <SwatchIcon class="w-4 h-4 mr-2 text-black" />
      <InputLabel>Change Color</InputLabel>
      <input
        type="color"
        :value="
          rgbToHex(
            extractRGB(code.color).r,
            extractRGB(code.color).g,
            extractRGB(code.color).b
          )
        "
        @input="updateColor($event, index, code.id)"
        class="absolute opacity-0 w-full max-h-full cursor-pointer"
        @click.stop
      />
    </button>

    <button
      @click="moveCodeUp(code.id)"
      v-if="index != 0"
      role="menuitem"
      class="w-full flex items-center py-2 px-4 hover:bg-gray-200 justify-start"
    >
      <ArrowUpIcon class="w-4 h-4 mr-2 text-black" />
      <InputLabel>Move Up</InputLabel>
    </button>
    <button
      class="w-full flex items-center py-2 px-4 hover:bg-gray-200 justify-start"
      @click="moveCodeDown(code.id)"
      v-if="index !== codes.length - 1"
      role="menuitem"
    >
      <ArrowDownIcon class="w-4 h-4 mr-2 text-black" />
      <InputLabel>Move Down</InputLabel>
    </button>
    <button
      class="w-full flex items-center py-2 px-4 hover:bg-gray-200 justify-start"
      @click="toggleCodeText(code.id)"
      role="menuitem"
    >
      <BookOpenIcon class="w-4 h-4 mr-2 text-black" />
      <InputLabel>Show Text</InputLabel>
    </button>
    <button
      class="w-full flex items-center py-2 px-4 hover:bg-gray-200 justify-start"
      @click="toggleOrResetOpacityForSingleCode(code.id)"
      role="menuitem"
    >
      <BookOpenIcon class="w-4 h-4 mr-2 text-black" />
      <InputLabel>Toggle Dimming</InputLabel>
    </button>
    <button
      class="w-full flex items-center py-2 px-4 hover:bg-gray-200 justify-start"
      @click="toggleVisibilityInEditor(code.id)"
      role="menuitem"
    >
      <rectangle-stack-icon class="w-4 h-4 mr-2 text-black" />
      <InputLabel>Toggle Code</InputLabel>
    </button>
    <button
      class="w-full flex items-center py-2 px-4 hover:bg-gray-200 justify-start"
      @click="deleteCode(code.id)"
      role="menuitem"
    >
      <XCircleIcon class="w-4 h-4 mr-2 text-black" />
      <InputLabel>Delete Code</InputLabel>
    </button>
    <button
      class="w-full flex items-center py-2 px-4 hover:bg-gray-200 justify-start"
      @click="openModal(index)"
      role="menuitem"
    >
      <MagnifyingGlassIcon class="w-4 h-4 mr-2 text-black" />
      <InputLabel>Consult Text</InputLabel>
    </button>
    <button
      v-if="level > 0"
      class="w-full flex items-center py-2 px-4 hover:bg-gray-200 justify-start"
      @click="upgradeToParent(code.id)"
      role="menuitem"
    >
      <arrow-up-icon class="w-4 h-4 mr-2 text-black" />
      <InputLabel>Upgrade to Main</InputLabel>
    </button>
    <button
      v-if="level === 2"
      class="w-full flex items-center py-2 px-4 hover:bg-gray-200 justify-start"
      @click="upHierarchy(code.id)"
      role="menuitem"
    >
      <arrow-up-icon class="w-4 h-4 mr-2 text-black" />
      <InputLabel>Up Hierarchy</InputLabel>
    </button>
    <button
      v-if="level < 2"
      class="w-full flex items-center py-2 px-4 hover:bg-gray-200 justify-start"
      @click="addCodeToChildrenRecursive(code.id)"
      role="menuitem"
    >
      <PlusIcon class="w-4 h-4 mr-2 text-black" />
      <InputLabel>Add SubCode</InputLabel>
    </button>
  </div>
</template>

<script>
import SwatchIcon from '@heroicons/vue/24/outline/SwatchIcon';
import {
  ArrowDownIcon,
  ArrowUpIcon,
  BookOpenIcon,
  MagnifyingGlassIcon,
  PlusIcon,
  RectangleStackIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline';
import InputLabel from '../../ui/form/InputLabel.vue';

export default {
  inject: [
    'moveCodeUp',
    'moveCodeDown',
    'toggleCodeText',
    'toggleOrResetOpacityForSingleCode',
    'toggleVisibilityInEditor',
    'deleteCode',
    'openModal',
    'addCodeToChildrenRecursive',
    'updateColor',
    'rgbToHex',
    'extractRGB',
    'handleDropdownClickOutside',
    'upgradeToParent',
    'upHierarchy',
  ],
  components: {
    InputLabel,
    SwatchIcon,
    BookOpenIcon,
    PlusIcon,
    RectangleStackIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    MagnifyingGlassIcon,
    XCircleIcon,
  },
  props: {
    index: {
      type: Number,
      required: true,
    },
    level: {
      type: Number,
      required: true,
    },
    code: {
      type: Object,
      required: true,
    },
    codes: {
      type: Array,
      required: true,
    },
    dropdownOpen: {
      type: Boolean,
      default: false,
    },
  },
};
</script>
