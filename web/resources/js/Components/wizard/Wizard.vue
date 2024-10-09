<script setup lang="ts">
import { useWizard } from './useWizard';
import {provide} from "vue";

const props = defineProps({
  start: String,
  title: String,
});
const WizardContext = useWizard({ start: props.start });
provide('wizardCtx', WizardContext)
provide('mainTitle', props.title)
</script>

<template>
    <div class="divide-y divide-foreground/50">
        <slot />
    </div>
</template>

<style scoped></style>
