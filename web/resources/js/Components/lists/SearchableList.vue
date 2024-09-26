<script setup lang="ts">
import InputField from '../../ui/form/InputField.vue';
import { onMounted, ref, watch } from 'vue';
import { cn } from '../../utils/css/cn';
import { debounce } from '../../utils/dom/debounce';

const props = defineProps({
  items: {
    type: Array,
    required: false,
  },
  filter: {
    type: Function,
    required: true,
  },
  searchclass: { type: String, required: false },
  ulclass: { type: String, required: false },
  liclass: { type: String, required: false },
});

const items = ref([]);
const term = ref('');

watch(
  term,
  debounce((value: string) => {
    if (value.trim() === '') {
      items.value = props.items ?? [];
    }
    if (value.length > 2) {
      items.value = props.items.filter((item) => props.filter(item, value));
    }
  }, 300)
);
onMounted(() => {
  items.value = props.items ?? [];
});
</script>

<template>
  <div :class="$props.class">
    <InputField
      type="search"
      :class="cn('', $props.searchclass)"
      placeholder="Search..."
      v-model="term"
    />
    <ul :class="cn('mt-3', $props.ulclass)">
      <li v-for="item in items" :class="$props.liclass">
        <slot name="item" v-bind="item"></slot>
      </li>
    </ul>
  </div>
</template>
