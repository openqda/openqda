<script setup lang="ts">
import { TrashIcon } from '@heroicons/vue/24/solid';
import Button from '../../../Components/interactive/Button.vue';
import { cn } from '../../../utils/css/cn';
import { ref, computed, watch, onMounted } from 'vue';
import InputField from '../../../form/InputField.vue';
import { vClickOutside } from '../../../Components/coding/clickOutsideDirective';
import CodingContextMenuItem from './CodingContextMenuItem.vue';
import { useContextMenu } from './useContextMenu';
import Separator from '../../../Components/layout/Separator.vue';

const { toDelete } = useContextMenu();
const emit = defineEmits(['code-selected', 'close']);
const props = defineProps({
  codes: Array,
  visible: Boolean,
});

const query = ref('');
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
        'fixed p-3 z-50 bg-surface border-background border-4 max-h-screen w-64 mt-1 overflow-auto rounded-md shadow-lg overflow-y-scroll',
        $props.visible !== true && 'hidden'
      )
    "
  >
    <div v-if="toDelete?.length" class="mb-6">
      <div class="block w-full text-xs font-semibold">Remove linked codes</div>
      <Button
        v-for="code in toDelete"
        variant="outline"
        class="w-full flex justify-between"
        :style="`background: ${code.color};`"
        @click.prevent="(e) => e.stopPropagation()"
      >
        <TrashIcon class="w-4 h-4" />
        <span>{{ code.name }}</span>
      </Button>
    </div>

      <div class="block w-full text-xs font-semibold">
          {{toDelete.length ? 'Reassign new code' : 'Assign code'}}
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
</template>

<style scoped></style>
