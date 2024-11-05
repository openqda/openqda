<script setup lang="ts">
import { ref } from 'vue';
import InputLabel from './InputLabel.vue';
import InputError from '../../../vendor/laravel/jetstream/stubs/inertia/resources/js/Components/InputError.vue';

const props = defineProps({
  value: String,
  name: String,
  options: Array,
  label: String,
  id: String,
});
const current = ref(props.value);
</script>

<template>
  <div class="input-group group contents">
    <InputLabel :for="props.name" :value="props.label" />
    <!-- Use an "onChange" listener to redirect the user to the selected tab URL. -->
    <select
      :id="props.name"
      v-model="current"
      :name="props.name"
      class="block w-full rounded-md focus:border-secondary focus:ring-secondary bg-surface text-foreground"
      @change="(e) => (current = e.target.value)"
    >
      <option disabled value="">(Select one)</option>
      <option v-for="opt in props.options" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>
    <InputError
      v-if="props.validation?.isValid === false"
      :message="props.validation?.error"
    />
  </div>
</template>

<style scoped></style>
