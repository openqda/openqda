<template>
  <div class="flex justify-between my-4">
    <div class="px-4 sm:px-0">
      <Headline3>
        <slot name="title" />
      </Headline3>

      <p class="mt-1 text-sm text-foreground/50 my-2">
        <slot name="description" />
      </p>
    </div>

    <div class="px-4 sm:px-0">
      <slot name="aside" />
    </div>
    <Separator />
  </div>
</template>
<script setup lang="ts">
import Headline3 from './layout/Headline3.vue';
import Separator from './layout/Separator.vue';
</script>
