<template>
    <div
    v-for="(file, index) in $props.files"
    class="my-10 border-l border-l-border"
    :key="`${file.id}-${index}`"
  >
    <div v-if="!!$props.checkedFiles.get(file.id)">
      <Headline3 class="ms-3">
        <span>{{ file.name }}</span>
        <button
            title="Hide this source"
            @click="$emit('remove', file.id)"
            class="float-right">
          <XMarkIcon class="h-5 w-5" />
        </button>
      </Headline3>
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
        No selections found in this source
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/solid';
import Headline3 from '../../Components/layout/Headline3.vue'
import Button from '../../Components/interactive/Button.vue'

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

const options = reactive({
    showEmpty: false
})

API.defineOptions({
    showEmpty: {
        type: Boolean,
        label: 'Show uncoded',
        title: 'Show sources that have no selections',
        labelClass: 'text-nowrap',
        defaultValue: true,
        model: options.showEmpty
    },
    sort: {
        type: String,
        label: null,
        groupClass: 'inline w-20',
        defaultValue: 'selections_count',
        options: [
            { value: 'name', label: 'by name'},
            { value: 'selections_count', label: 'by number of selections'},
        ]
    }
})

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

watch(props , API.debounce(rebuildList, 100));

onMounted(() => {
  rebuildList();
});
</script>

<style scoped></style>
