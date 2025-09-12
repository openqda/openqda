<template>
    <Disclosure as="div" v-slot="{ open }" :default-open="props.defaultOpen">
        <!-- Use the `open` state to conditionally change the direction of an icon. -->
        <DisclosureButton class="py-2">
            <slot name="trigger" :open="open" />
        </DisclosureButton>
        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-out"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
        <DisclosurePanel><slot name="content" /></DisclosurePanel>
        </transition>
    </Disclosure>
</template>

<script setup>
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
} from '@headlessui/vue'

const props = defineProps({
    defaultOpen: {
        type: Boolean
    }
});
</script>

<style>
</style>
