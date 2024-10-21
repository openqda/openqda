<script setup lang="ts">
import Button from '../../Components/interactive/Button.vue';
import { cn } from '../../utils/css/cn';
import {ref, computed, watch, onMounted} from 'vue';
import InputField from "../../form/InputField.vue";
import { vClickOutside } from "../../Components/coding/clickOutsideDirective";

const emit = defineEmits(['codes-selected', 'close'])
const props = defineProps({
    codes: Array,
    visible: Boolean,
});

const query = ref('');
const searchField = ref()
let filteredCodes = computed(() =>
  query.value === ''
    ? props.codes
    : props.codes.filter((code) =>
        code.name
          .toLowerCase()
          .replace(/\s+/g, '')
          .includes(query.value.toLowerCase().replace(/\s+/g, ''))
      )
);
const changeRGBOpacity = (rgba, opacity) => {
    const rgbaValues = rgba.match(/[\d.]+/g);
    if (rgbaValues && rgbaValues.length >= 3) {
        return `rgba(${rgbaValues[0]}, ${rgbaValues[1]}, ${rgbaValues[2]}, ${opacity})`;
    }
    return rgba;
}
</script>

<template>
  <div
    v-click-outside="{ callback: () => emit('close')}"
    id="contextMenu"
    :class="
      cn(
        'fixed p-3 z-50 bg-black max-h-screen w-64 mt-1 overflow-auto rounded-md shadow-xl overflow-y-scroll',
        $props.visible !== true && 'hidden'
      )
    "
  >
    <Button
      id="deleteCodeBtn"
      variant="outline"
      class="w-full"
      @click.prevent="e => e.stopPropagation()"
    >
      Remove this code annotation
    </Button>


      <!-- text input field to filter by name -->
      <InputField
          v-model="query"
          placeholder="Filter codes by name"
          class="placeholder-foreground/50" />
    <ul>
      <template v-for="(code, index) in filteredCodes" :key="code.id">
        <li
          class="px-4 py-2 my-2 group text-sm rounded-md cursor-pointer hover:bg-white selection-none contextMenuOption"
          :style="{
            backgroundColor: changeRGBOpacity(code.color, 1),
          }"
          @click="emit('code-selected', { index, id: code.id })"
        >
            <span class="group-hover:bg-surface group-hover:text-foreground px-1">
                {{ code.name }}
            </span>
        </li>

        <!-- Render children, if any -->
        <ul v-if="code.children?.length > 0" class="pl-6">
          <template
            v-for="(childCode, childIndex) in code.children"
            :key="childCode.id"
          >
            <li
              class="px-4 py-2 my-2 text-sm rounded-md cursor-pointer hover:bg-white selection-none contextMenuOption"
              :style="{
                backgroundColor: changeRGBOpacity(childCode.color, 1),
              }"
              @click="
                highlightAndAddTextToCode(childIndex, childCode.id, code.id)
              "
            >
              {{ childCode.title }}
            </li>
            <!-- Render grandchildren, if any -->
            <ul v-if="childCode.children.length > 0" class="pl-6">
              <li
                v-for="(grandChildCode, grandChildIndex) in childCode.children"
                :key="grandChildCode.id"
                class="px-4 py-2 my-2 text-sm rounded-md cursor-pointer hover:bg-white selection-none contextMenuOption"
                :style="{
                  backgroundColor: changeRGBOpacity(grandChildCode.color, 1),
                }"
                @click="
                  highlightAndAddTextToCode(
                    grandChildIndex,
                    grandChildCode.id,
                    childCode.id
                  )
                "
              >
                {{ grandChildCode.title }}
              </li>
            </ul>
          </template>
        </ul>
      </template>
    </ul>
  </div>
</template>

<style scoped></style>
