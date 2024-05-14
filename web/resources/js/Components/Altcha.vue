<template>
  <altcha-widget
    challengeurl="/altcha-challenge"
    @statechange="stateChanged"
    auto="onload"
  ></altcha-widget>
</template>

<script setup>
import 'altcha'

const emit = defineEmits(['update:modelValue'])
const stateChanged = (ev) => {
  if (ev.detail.state === 'verified') {
    emit('update:modelValue', ev.detail.payload)
  }
}
</script>
