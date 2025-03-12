<script setup lang="ts">
import { ref, watch } from 'vue';
import { useCodes } from '../../../domain/codes/useCodes';
import CodeTreeItem from './CodeTreeItem.vue';
import { useCodebookOrder } from '../../../domain/codebooks/useCodebookOrder';
import { attemptAsync } from '../../../Components/notification/attemptAsync';
import CodebookRenderer from './CodebookRenderer.vue';
import { useLocalSearch } from '../../../utils/search/useLocalSearch';
import InputField from '../../../form/InputField.vue';
import { toArray } from '../../../utils/array/toArray';

const props = defineProps({
  codes: Array,
  codebook: Object,
});

const { observe } = useCodes();

//------------------------------------------------------------------------
// CODEBOOKS
//------------------------------------------------------------------------
const {
  changed: codebookOrderChanged,
  getSortOrderBy,
  updateSortOrder,
} = useCodebookOrder();

//------------------------------------------------------------------------
// CODE ORDER AND FILTER
//------------------------------------------------------------------------
const bySortOrder = getSortOrderBy(props.codebook);
const { searchTerm } = useLocalSearch({
  fields: { name: String, id: String },
  recursive: {
    field: 'children',
    depth: 2,
  },
});
const codeList = ref(toArray(props.codes));
const sortCodes = (list = []) => {
  list.sort(bySortOrder);
  list.forEach((entry) => {
    if (entry.children?.length > 0) {
      sortCodes(entry.children);
    }
  });
};
sortCodes(codeList.value);

//------------------------------------------------------------------------
// OBSERVERS / WATCHERS
//------------------------------------------------------------------------
// we use a deep watcher on the codebooks, flagged for change,
// because this is less demanding than deep-watching the recursive code-list
watch(
  () => codebookOrderChanged,
  async (changed) => {
    if (changed.value[props.codebook.id]) {
      await attemptAsync(() =>
        updateSortOrder({ target: codeList.value, codebook: props.codebook })
      );
    }
  },
  { deep: true, immediate: true }
);

observe('store/codes', {
  added: (docs) => {
    docs.forEach((doc) => {
      if (doc.codebook === props.codebook.id && !doc.parent) {
        codeList.value.push(doc);
      }
    });
  },
  removed: (docs) => {
    docs.forEach((doc) => {
      if (doc.codebook === props.codebook.id) {
        const index = codeList.value.findIndex((d) => d.id === doc.id);
        if (index > -1) {
          codeList.value.splice(index, 1);
        }
      }
    });
  },
});
</script>

<template>
  <div class="w-full">
    <CodebookRenderer
      :codebook="codebook"
      :codes="codeList"
      :can-toggle="!searchTerm.length"
      :can-sort="!searchTerm.length"
    />
    <InputField type="search" placeholder="Search..." v-model="searchTerm" />
    <div v-if="!codeList?.length" class="text-sm text-foreground/50">
      <p v-if="searchTerm?.length">No entries matched for your search.</p>
    </div>
    <CodeTreeItem
      v-model="codeList"
      class="py-4"
      :search="searchTerm"
      :group-id="props.codebook.id"
      :parent-id="null"
    />
  </div>
</template>
