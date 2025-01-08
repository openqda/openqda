<template>
  <div
    v-if="msg"
    class="absolute top-0 right-0 m-2 w-60 items-center py-2 px-3 mb-2"
  >
    <div
      :class="[
        colors.color({ bg: msg.type, border: msg.type }),
        'border-l-4 text-white w-full p-2 shadow-md',
      ]"
    >
      {{ msg.message }}
    </div>
    <div
      :class="[
        'border-l-4  h-1 transition-[width] duration-1000',
        colors.color({ bg: msg.type, border: msg.type }),
      ]"
      :style="{ width: `${widthPercentage}%` }"
    ></div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useFlashMessage, flashMessage } from './flashMessage.js';
import { ColorMap } from '../../utils/colors/ColorMap.js';
const props = defineProps(['message', 'flash']);
const getMessage = useFlashMessage();
const msg = ref(null);
const widthPercentage = ref(100);
const colors = new ColorMap({
  bg: {
    success: 'bg-confirmative/90',
    error: 'bg-destructive/90',
    default: 'bg-primary/90',
  },
  border: {
    success: 'border-confirmative',
    error: 'border-destructive',
    default: 'border-primary',
  },
});

onMounted(() => {
  watch(
    getMessage,
    (value) => {
      widthPercentage.value = 0;
      msg.value = value;
      setTimeout(() => {
        msg.value = null;
        widthPercentage.value = 100;
      }, 3000);
    },
    { immediate: true, deep: true }
  );

  if (props.flash?.message) {
    flashMessage(props.flash.message);
  }
});
</script>

<style scoped>
.h-1 {
  height: 4px;
}
</style>
