<template>
  <ul>
    <li>
      📔 In case you need help, please consult our
      <Link href="https://openqda.github.io/user-docs/" :external="true"
        >OpenQDA user documentation</Link
      >
    </li>
    <li>
      🗣️ You can give technical
      <Link href="https://github.com/orgs/openqda/discussions" :external="true"
        >feedback directly to our developers</Link
      >
    </li>
    <li>
      📧 Contact us directly via email:
      <Link href="mailto:openqda@uni-bremen.de" :external="true"
        >openqda@uni-bremen.de</Link
      >
      or use the contact form in the
      <button
        class="text-foreground/80 hover:text-secondary hover:underline text-nowrap inline-flex items-center underline-offset-3 cursor-pointer"
        @click="openHelp"
      >
        feedback dialog</button
      >.
    </li>
  </ul>
</template>

<script setup>
import Link from './Link.vue';
import { useHelpDialog } from '../dialogs/help/useHelpDialog.js';
const { open: openHelp } = useHelpDialog();
</script>

<style scoped></style>
