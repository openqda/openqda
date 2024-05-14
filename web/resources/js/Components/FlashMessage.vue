<template>
  <div
    v-if="flash"
    class="absolute bottom-0 right-0 m-2 w-60 items-center py-2 px-3 mb-2"
  >
    <div
      class="bg-cerulean-500 border-l-4 border-cerulean-700 text-white w-full p-2 shadow-md"
    >
      {{ flash.message }}
    </div>
    <div
      class="border-l-4 border-cerulean-700 bg-cerulean-500 h-1 transition-all duration-100"
      :style="{ width: `${widthPercentage}%` }"
    ></div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

// Initialize flash as an empty object
defineProps(['message', 'flash'])

const widthPercentage = ref(100)

const startTimer = () => {
  let counter = 0
  const intervalId = setInterval(() => {
    counter += 0.1 // Smaller step to make it smoother
    const percentage = (counter / 50) * 100
    widthPercentage.value = 100 - percentage

    if (counter >= 50) {
      usePage().props.flash.message = ''
      clearInterval(intervalId)
    }
  }, 10) // Smaller interval to make it smoother
}

onMounted(() => {
  startTimer()
})
</script>

<style scoped>
.h-1 {
  height: 4px;
}
</style>
