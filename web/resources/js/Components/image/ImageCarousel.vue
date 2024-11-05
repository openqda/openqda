<script setup>
defineProps({
  sources: Array,
});
</script>

<template>
  <div class="mx-auto max-w-7xl sm:px-2 lg:px-4">
    <div
      class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-12 px-4 lg:max-w-none xl:grid-cols-2 lg:gap-y-16"
    >
      <div v-for="source in sources" :key="source.name" class="sm:flex">
        <div class="sm:flex-shrink-0">
          <div class="flow-root">
            <img
              class="max-w-64"
              :src="source.img"
              :alt="source.name"
              :title="source.name"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
