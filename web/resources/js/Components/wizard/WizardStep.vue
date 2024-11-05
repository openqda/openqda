<script setup lang="ts">
import { inject } from 'vue';
import WizardOptions from './WizardOptions.vue';

const WizardCtx = inject('wizardCtx');
const mainTitle = inject('mainTitle');
const props = defineProps({
  name: String,
  back: {
    type: Boolean,
    optional: true,
  },
  title: String,
  options: {
    type: Array,
    optional: true,
  },
});
</script>

<template>
  <div v-if="WizardCtx.show(props.name)">
    <div class="text-sm tracking-wide py-2 flex justify-between">
      <span>{{ `${WizardCtx.step(props.name)}. ${props.title}` }}</span>
      <span
        v-if="WizardCtx.hasPrev()"
        class="cursor-pointer hover:underline"
        type="button"
        @click="WizardCtx.back()"
      >
        Go Back
      </span>
    </div>
    <slot />
    <WizardOptions
      v-if="props.options"
      :options="props.options"
      class="mt-6"
      @selected="(option) => WizardCtx.next(option.next)"
    />
  </div>
</template>

<style scoped></style>
