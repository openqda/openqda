<script setup lang="ts"></script>

<template>
  <span class="contrast-text"><slot /></span>
</template>

<style>
.contrast-text {
  background: inherit;
  background-clip: text;
  color: transparent;
  filter: invert(1) grayscale(1) contrast(12);
}
</style>
