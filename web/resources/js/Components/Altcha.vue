<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';

// Importing altcha package will introduce a new element <altcha-widget>
import 'altcha';

const altchaWidget = ref<HTMLElement | null>(null);
const props = defineProps({
  payload: {
    type: String,
    required: false,
  },
});
const emit = defineEmits(['update:payload']);
const internalValue = ref(props.payload);

watch(internalValue, (v) => {
  emit('update:payload', v || '');
});

const onStateChange = (ev) => {
  if ('detail' in ev) {
    const { payload, state } = ev.detail;
    if (state === 'verified' && payload) {
      internalValue.value = payload;
    } else {
      internalValue.value = '';
    }
  }
};

onMounted(() => {
  if (altchaWidget.value) {
    altchaWidget.value.addEventListener('statechange', onStateChange);
  }
});

onUnmounted(() => {
  if (altchaWidget.value) {
    altchaWidget.value.removeEventListener('statechange', onStateChange);
  }
});
</script>

<template>
  <!-- Configure your `challengeurl` and remove the `test` attribute, see docs: https://altcha.org/docs/v2/widget-integration/ -->
  <altcha-widget
    ref="altchaWidget"
    challengeurl="/altcha-challenge"
  ></altcha-widget>
</template>
