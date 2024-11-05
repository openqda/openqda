<script setup>
import { ref } from 'vue'
import CodeListItem from './CodeListItem.vue';
import { useCodes } from './useCodes.js';
import { computed } from 'vue';
import Headline3 from '../../Components/layout/Headline3.vue';
import { cn } from '../../utils/css/cn.js';
import { ChevronRightIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/solid/index.js'
import Button from '../../Components/interactive/Button.vue';
import draggable from "vuedraggable";

const { codes, toggleCodebook } = useCodes();
const props = defineProps({
  codebook: Object,
});

const drag = ref(false)
const open = ref(true);
const byCodebook = computed(() => {
  const codebookId = props.codebook.id;
  return codes.value.filter((code) => code.codebook === codebookId);
});
const dragOptions = ref({
    animation: 200,
    group: "description",
    disabled: false,
    ghostClass: "ghost"
})
</script>

<template>
  <div class="flex align-center">
    <Button
      :title="open ? 'Close code list' : 'Open code list'"
      variant="outline"
      size="sm"
      class="!px-1 !py-1 !mx-0 !my-0 bg-transparent"
      @click.prevent="open = !open"
    >
      <ChevronRightIcon :class="cn('w-4 h-4', open && 'rotate-90')" />
    </Button>
    <headline3 class="ms-4 flex-grow me-2">{{ codebook.name }}</headline3>
      <button
          class="p-0 m-0 text-foreground/80"
          @click="toggleCodebook(codebook)"
          :title="codebook.active ? 'Codebook enabled, click to disable' : 'Codebook disabled, click to enable'"
      >
          <EyeSlashIcon
              v-if="codebook.active === false"
              class="w-4 h-4 text-foreground/50"
          />
          <EyeIcon v-else class="w-4 h-4" />
      </button>
  </div>
  <div v-if="open">
    <p class="text-foreground/50" v-if="byCodebook && byCodebook.length === 0">
      No codes available, please activate at least one codebook.
    </p>
      <ul>
            <CodeListItem v-for="code in byCodebook" :code="code" :key="code.id" />
    </ul>
  </div>
</template>

<style scoped></style>
