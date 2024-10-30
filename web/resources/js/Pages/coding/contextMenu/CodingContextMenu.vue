<script setup lang="ts">
import { TrashIcon, ArrowPathRoundedSquareIcon } from '@heroicons/vue/24/solid';
import Button from '../../../Components/interactive/Button.vue';
import { cn } from '../../../utils/css/cn';
import { ref, computed, watch, onMounted } from 'vue';
import InputField from '../../../form/InputField.vue';
import { vClickOutside } from '../../../Components/coding/clickOutsideDirective';
import CodingContextMenuItem from './CodingContextMenuItem.vue';
import {useSelections} from "../selections/useSelections";

const { toDelete } = useSelections();
const emit = defineEmits(['code-selected', 'close']);
const props = defineProps({
  selection: Object,
  codes: Array,
  visible: Boolean,
});

const query = ref('');
const reassign = ref(null)
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
</script>

<template>
  <div
    v-click-outside="{ callback: () => emit('close') }"
    id="contextMenu"
    :class="
      cn(
        'fixed p-3 z-50 bg-surface border-background border-4 max-h-screen w-min-64 w-1/5 mt-1 overflow-auto rounded-md shadow-lg overflow-y-scroll',
        $props.visible !== true && 'hidden'
      )
    "
  >
    <div v-if="toDelete?.length" class="mb-6">
      <div class="block w-full text-xs font-semibold">Linked codes</div>
        <div class="flex items-baseline space-x-2 text-sm"  v-for="code in toDelete">
            <span class="flex-grow p-2 my-1 rounded-md" :style="`background: ${code.color};`">{{ code.name }}</span>
            <Button
                title="Reassign another code"
                variant="outline"
                class="p-2"
                @click.prevent="(e) => e.stopPropagation()"
            >
                <ArrowPathRoundedSquareIcon class="w-4 h-4" />
            </Button>
            <Button
                title="Delete selection with this code"
                variant="destructive"
                class="p-2"
                @click.prevent="(e) => e.stopPropagation()"
            >
                <TrashIcon class="w-4 h-4" />
            </Button>
        </div>
    </div>
      <div v-if="!toDelete?.length || reassign">
      <div class="block w-full text-xs font-semibold">
        {{ toDelete.length ? 'Reassign new code' : 'Assign code' }}
      </div>

      <!-- text input field to filter by name -->
      <InputField
        v-model="query"
        placeholder="Filter codes by name"
        class="placeholder-foreground/50"
      />
      <ul>
        <CodingContextMenuItem
          v-for="code in filteredCodes"
          :key="code.id"
          :code="code"
          :parent="null"
        />
      </ul>
    </div>
  </div>
</template>

<style scoped></style>
