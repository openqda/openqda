<template>
    <h3 :class="['font-semibold leading-6 tracking-wide text-foreground', props.class]">
        <slot></slot>
    </h3>
</template>
<script setup>
const props = defineProps(['class']);
</script>
