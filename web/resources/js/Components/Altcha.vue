<template>
  <altcha-widget
    v-once
    id="altcha_widget"
    class="text-secondary-foreground border-0"
    challengeurl="/altcha-challenge"
    @statechange="stateChanged"
    auto="off"
  ></altcha-widget>
</template>

<script setup>
import 'altcha';

const emit = defineEmits(['update:modelValue']);
const stateChanged = (ev) => {
  if (ev.detail.state === 'verified') {
    emit('update:modelValue', ev.detail.payload);
  }
};
</script>
