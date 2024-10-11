<script setup>
import CodeListItem from './CodeListItem.vue'

const props = defineProps(['codes'])
</script>

<template>
<ul>
    <CodeListItem v-for="code in props.codes" :code="code" />
</ul>
</template>

<style scoped>
</style>
