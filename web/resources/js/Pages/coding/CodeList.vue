<script setup>
import CodeListItem from './CodeListItem.vue'
import { useCodes } from './useCodes.js'

const { codes, codebooks } = useCodes()
</script>

<template>
    <p class="text-foreground/50" v-if="filtered && filtered.length === 0">
        No codes available, please activate at least one codebook.
    </p>
<ul>
    <CodeListItem v-for="code in codes" :code="code" :key="code.id" />
</ul>
</template>

<style scoped>
</style>
