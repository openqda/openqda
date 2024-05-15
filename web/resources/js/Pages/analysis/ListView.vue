<template>
  <div
    v-for="(file, index) in $props.files"
    class="my-10 border-l border-l-silver-300"
    :key="`${file.id}-${index}`"
  >
    <div v-if="!!$props.checkedFiles.get(file.id)">
      <h3 class="font-semibold tracking-wide px-4">
        <span>{{ file.name }}</span>
        <XMarkIcon
          class="float-right h-5 w-5 text-silver-300 hover:text-porsche-400 cursor-pointer"
          @click="$emit('remove', file.id)"
        />
      </h3>
      <div
        v-for="code in codesList.get(file.id)"
        :key="code.id"
        :style="{ borderColor: code.color }"
        class="border-l border-r ml-2 mt-3"
      >
        <div class="p-2" :style="{ backgroundColor: code.color }">
          <span>{{ code.name }}</span>
        </div>
        <div v-for="selection in code.text" :key="selection.source_id">
          <div
            v-if="file.id === selection.source_id"
            class="my-1 border-b"
            :style="{ borderBottomColor: code.color }"
          >
            <div
              class="text-sm text-silver-500 min-w-[6rem] flex justify-between p-2"
            >
              <div>{{ selection.start }} - {{ selection.end }}</div>
              <span>
                <span>{{
                  new Date(selection.updatedAt).toLocaleDateString()
                }}</span
                ><span>, </span>
                <span>by {{ selection.createdBy }}</span>
              </span>
            </div>
            <div class="p-2 flex-grow">{{ selection.text }}</div>
          </div>
        </div>
      </div>
      <div v-if="!codesList.has(file.id)" class="ml-2 mt-2 p-2 bg-silver-100">
        No codes
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { debounce } from '../../utils/debounce.js';
import { XMarkIcon } from '@heroicons/vue/24/solid';

defineEmits(['remove']);
const props = defineProps([
  'files',
  'codes',
  'checkedFiles',
  'checkedCodes',
  'hasSelections',
  'api',
]);
const codesList = ref(new Map());
const API = props.api;

const rebuildList = () => {
  props.files.forEach((file) => {
    const c = API.getCodesForFile(file);
    if (c.length) {
      codesList.value.set(file.id, c);
    } else {
      codesList.value.delete(file.id);
    }
  });
};

watch(props, debounce(rebuildList, 100));

onMounted(() => {
  rebuildList();
});
</script>

<style scoped></style>
