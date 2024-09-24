<template>
  <div
    v-if="flash"
    class="absolute bottom-0 right-0 m-2 w-60 items-center py-2 px-3 mb-2"
  >
    <div
      :class="[
        colors.color({ bg: flash.type, border: flash.type }),
        'border-l-4 text-white w-full p-2 shadow-md',
      ]"
    >
      {{ flash.message }}
    </div>
    <div
      :class="[
        'border-l-4  h-1 transition-all duration-100',
        colors.color({ bg: flash.type, border: flash.type }),
      ]"
      :style="{ width: `${widthPercentage}%` }"
    ></div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { flashMessage } from './flashMessage.js';
import { ColorMap } from '../../utils/colors/ColorMap.js';

// Initialize flash as an empty object
defineProps(['message', 'flash']);

const widthPercentage = ref(100);
const colors = new ColorMap({
  bg: {
    success: 'bg-porsche-700',
    error: 'bg-red-400',
    default: 'bg-cerulean-500',
  },
  border: {
    success: 'border-porsche-300',
    error: 'border-red-600',
    default: 'border-cerulean-700',
  },
});

const startTimer = () => {
  let counter = 0;
  const intervalId = setInterval(() => {
    counter += 0.1; // Smaller step to make it smoother
    const percentage = (counter / 50) * 100;
    widthPercentage.value = 100 - percentage;

    if (counter >= 50) {
      flashMessage('', { type: 'default' });
      clearInterval(intervalId);
    }
  }, 10); // Smaller interval to make it smoother
};

onMounted(() => {
  startTimer();
});
</script>

<style scoped>
.h-1 {
  height: 4px;
}
</style>
