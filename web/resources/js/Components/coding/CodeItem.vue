<script setup>
/*
 | Visual representation of a code as list-item without
 | further interactivity.
 */
import { inject, onMounted, ref, watchEffect } from 'vue';
import {
    ChevronDownIcon,
    ChevronUpIcon,
    EllipsisVerticalIcon,
    PencilSquareIcon,
} from '@heroicons/vue/24/outline';
import { XCircleIcon as XCircleSolidIcon } from '@heroicons/vue/24/solid';
import DropdownMenu from './DropdownMenu.vue';
import { vClickOutside } from './clickOutsideDirective.js';
import CodeLabel from './CodeLabel.vue';

const props = defineProps(['code', 'index', 'level', 'parentId']);
defineEmits(['child-drag']);

const code = ref(props.code);
const childCodeItems = ref(null);
const codeItemComponents = ref([]); // Initialize an empty array
const handleDropdownClickOutside = inject('handleDropdownClickOutside');
const handleDragCodeStart = inject('handleDragCodeStart');
const codes = inject('codes');
const deleteTextFromCode = inject('deleteTextFromCode');
const handleDrag = inject('handleDrag');

const lowerOpacityOfOthers = inject('lowerOpacityOfOthers');
const resetOpacityOfOthers = inject('resetOpacityOfOthers');
const saveCodeTitle = inject('saveCodeTitle');
const saveDescription = inject('saveDescription');
const toggleCodeText = inject('toggleCodeText');
const scrollToTextPosition = inject('scrollToTextPosition');
const handleCodeDescriptionClickOutside = inject(
    'handleCodeDescriptionClickOutside'
);
const openDescription = inject('openDescription');

const handleChildDrag = (event, index, parentId, codeId) => {
    handleDrag(event, index, parentId, codeId);
};

// Function to collect all child components
const collectChildren = () => {
    // Clean the array first
    codeItemComponents.value = [];

    // Use whatever logic you have to find your child components.
    // For this example, let's say we find them and put them in a variable called 'foundChildren'.
    const foundChildren = document.querySelectorAll('.grandchild');

    // Add them to codeItemComponents
    codeItemComponents.value.push(...foundChildren);

    // Ask each child to collect its own children
    codeItemComponents.value.forEach((childComponent) => {
        if (typeof childComponent.collectChildren === 'function') {
            childComponent.collectChildren();
        }
    });
};

defineExpose({
    childCodeItems,
    codeItemComponents,
    collectChildren, // expose this function
});

onMounted(async () => {
    collectChildren();
});

watchEffect(() => {
    // Go through all children and ask them to refresh their children
    codeItemComponents.value.forEach((childComponent) => {
        if (typeof childComponent.collectChildren === 'function') {
            childComponent.collectChildren();
        }
    });
});
</script>
<template>
  <li
    ref="childCodeItems"
    :class="`ml-${6 * level} ${
      code.editable ? 'border-2 border-orange-500 p-0' : ''
    } ${level === 1 ? 'child' : level === 2 ? 'grandchild' : ''}`"
    @mouseover="lowerOpacityOfOthers(code.id)"
    @mouseout="resetOpacityOfOthers(code.id)"
    @blur="
      (event) => {
        event.stopPropagation();
        r;
      }
    "
    :draggable="!code.editable"
    @dragstart="handleChildDrag($event, index, parentId, code.id)"
    class="p-2 mb-2 text-black rounded select-none :focus:outline-none :focus:ring-2 :focus:ring-sky-500 :focus:border-sky-500"
    :style="{ backgroundColor: code.color }"
    :data-id="code.id"
  >
    <div class="flex items-center justify-between space-x-4">
      <CodeLabel
        @click.stop
        :label="code.title"
        :index="index"
        :editable="code.editable"
        @startedit="code.editable = true"
        @endedit="saveCodeTitle(code.id, $event)"
        @changed="saveCodeTitle(code.id, $event)"
      />

      <div
        class="relative inline-block group"
        @click.stop
        v-click-outside="{
          callback: handleCodeDescriptionClickOutside,
        }"
      >
        <div class="flex space-x-4">
          <ChevronUpIcon
            v-if="code.showDescription"
            class="-ml-0.5 h-5 w-5"
            aria-hidden="true"
            @click="code.showDescription = !code.showDescription"
          ></ChevronUpIcon>
          <ChevronDownIcon
            v-else-if="code.description.length > 0"
            class="-ml-0.5 h-5 w-5"
            aria-hidden="true"
            @click="code.showDescription = !code.showDescription"
          ></ChevronDownIcon>
          <PencilSquareIcon
            @click.stop="openDescription(code)"
            @mouseover="code.showHoverDescription = true"
            @mouseleave="code.showHoverDescription = false"
            class="w-5 h-5 text-black group/item"
          ></PencilSquareIcon>
        </div>
        <textarea
          @change="saveDescription(code)"
          v-if="code.showEditDescription"
          v-model="code.description"
          class="absolute w-64 h-64 post-it right-full"
          rows="4"
          cols="50"
        >
        </textarea>
        <div
          v-if="
            !code.showEditDescription &&
            code.description.length > 0 &&
            code.showHoverDescription
          "
          class="absolute z-10 px-2 py-1 text-xs text-white whitespace-pre-line bg-gray-700 rounded -bottom-6 right-6"
        >
          {{ code.description }}
        </div>
      </div>
      <div class="relative flex items-center">
        <div
          @click="toggleCodeText(code.id)"
          class="text-xs leading-relaxed bg-neutral-100 rounded px-1.5"
        >
          {{ code.text.length }}
        </div>
        <button class="z-0 ml-2">
          <EllipsisVerticalIcon
            @click="
              code.dropdownOpen = !code.dropdownOpen;
              code.justOpened = true;
            "
            class="w-5 h-5 text-black"
          />
        </button>
        <DropdownMenu
          v-if="code.dropdownOpen"
          v-click-outside="{ callback: handleDropdownClickOutside }"
          :index="index"
          :code="code"
          :codes="codes"
          :level="level"
        />
      </div>
    </div>
    <div
      v-if="code.description.length > 0 && code.showDescription"
      class="px-2 py-1 mt-1 text-xs antialiased text-white whitespace-pre-line bg-gray-700 rounded -bottom-6 right-6"
    >
      {{ code.description }}
    </div>
    <transition
      name="accordion"
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-1 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-1 opacity-0"
    >
      <div
        v-if="code.showText"
        class="text-sm divide-y divide-silver-300 cursor-text"
        key="code.text"
      >
        <div v-for="(item, textIndex) in code.text" :key="textIndex" class="">
          <div
            class="h-auto text-xs flex bg-gray-300 border border-solid border-1 border-b-silver-300 p-1 font-mono"
          >
            <div
              class="p-0 cursor-pointer"
              @click="scrollToTextPosition(item.start, item.end)"
            >
              {{ item.start }} - {{ item.end }}
            </div>
            <button
              class="flex ml-auto cursor-pointer"
              @click="deleteTextFromCode(item, textIndex, code.id)"
            >
              <XCircleSolidIcon
                class="w-4 h-4 text-silver-900 hover:text-silver-500"
              ></XCircleSolidIcon>
            </button>
          </div>
          <div class="p-2">
            {{ item.text }}
          </div>
          <!-- Don't render <hr> after last item -->
        </div>
      </div>
    </transition>
  </li>

  <template v-if="code.children.length > 0">
    <CodeItem
      ref="codeItemComponents"
      v-for="(childCode, childIndex) in code.children"
      :key="childCode.id"
      :code="childCode"
      :index="childIndex"
      :parentId="code.id"
      @child-drag="handleDragCodeStart"
      :level="2"
    />
  </template>
</template>
<style scoped>
[contenteditable]:focus {
  outline: none !important; /* Removes the browser default outline */
  border: 0px solid;
}
</style>
