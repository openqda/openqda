<template>
    <h3 :class="['font-semibold leading-6 text-lg text-porsche-400', props.class]">
        <slot></slot>
    </h3>
</template>
<script setup>
const props = defineProps(['class']);
</script>
