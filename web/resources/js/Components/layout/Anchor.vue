<template>
    <a
        :href="props.href"
        referrerpolicy="no-referrer"
        :target="props.external ? '_blank' : undefined"
        class="hover:text-porsche-400 rounded-md focus:outline-none focus:ring-1 focus:ring-offset-3 focus:ring-porsche-400 focus:text-porsche-400 inline-block">
        <span class="flex flex-row items-center">
            <slot></slot>
            <ArrowTopRightOnSquareIcon v-if="props.external" class="w-4 h-4 ml-1" />
        </span>
    </a>
</template>

<script setup>
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/solid'

const props = defineProps(['href', 'label', 'external'])
</script>

<style scoped></style>
