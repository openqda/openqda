<template>
  <footer class="bg-surface text-foreground border-t">
    <nav class="w-full" aria-label="Footer">
      <div class="container mx-auto px-4 py-3">
        <div class="flex flex-wrap justify-center items-center gap-6">
          <Anchor
            v-for="item in navigation.main"
            :key="item.name"
            :href="item.href"
            :external="item.external"
            class="tracking-wide text-sm leading-6"
          >
            {{ item.name }}
          </Anchor>
        </div>
      </div>
    </nav>
    <p class="mt-6 text-center text-sm leading-5">
      &copy; {{ currentYear }} OpenQDA
    </p>
  </footer>
</template>

<script setup>
import { ref } from 'vue';
import Anchor from '../Components/layout/Anchor.vue';

const currentYear = ref(new Date().getFullYear());
const navigation = {
  main: [
    { name: 'Imprint', href: '/imprint' },
    { name: 'Privacy', href: '/privacy' },
    { name: 'FAQ', href: '/faq' },
    { name: 'License', href: '/license' },
    {
      name: 'Documentation',
      href: 'https://openqda.github.io/user-docs/',
      external: true,
    },
    { name: 'GitHub', href: 'https://github.com/openqda', external: true },
  ],
  social: [],
};
</script>
