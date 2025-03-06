<template>
  <div
    v-if="msg"
    class="absolute top-0 right-0 m-2 w-60 items-center py-2 px-3 mb-2"
    :style="{ zIndex: 9999 }"
  >
    <div
      :class="[
        colors.color({ bg: msg.type, border: msg.type }),
        'border-l-4 text-white w-full p-2 shadow-md',
      ]"
    >
      {{ msg.message }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onUnmounted } from 'vue';
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

let timeout;

const clean = () => {
  msg.value = null;
  widthPercentage.value = 100;
  clearTimeout(timeout);
};

onMounted(() => {
  watch(
    getMessage,
    (value) => {
      if (!value) {
        return clean();
      }
      msg.value = value;
      timeout = setTimeout(() => clean(), 3000);
    },
    { immediate: true, deep: true }
  );

  if (props.flash?.message) {
    flashMessage(props.flash.message);
  }
});

onUnmounted(() => {
  clean();
  flashMessage(null);
});
</script>

<style scoped>
.h-1 {
  height: 4px;
}

.progress {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 1s;
}
</style>
