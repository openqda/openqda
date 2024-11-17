<script setup lang="ts">
</script>

<template>
<span class="bordered-text"><slot /></span>
</template>

<style>
.bordered-text {
    background: inherit;
    background-clip: text;
    color: transparent;
    filter: invert(1) grayscale(1) contrast(12)
}
</style>
