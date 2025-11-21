<template>
  <footer class="bg-surface text-foreground border-t">
    <div class="mx-auto max-w-7xl overflow-hidden px-6 py-20 sm:py-24 lg:px-8">
      <nav
        class="-mb-6 columns-2 sm:flex sm:justify-center sm:gap-12"
        aria-label="Footer"
      >
        <div v-for="item in navigation.main" :key="item.name" class="pb-6">
          <Anchor
            :href="item.href"
            :external="item.external"
            class="tracking-wide text-sm leading-6"
          >
            {{ item.name }}
          </Anchor>
        </div>
      </nav>
      <div class="mt-10 flex justify-center gap-10">
        <a
          v-for="item in navigation.social"
          :key="item.name"
          :href="item.href"
          class="hover:text-gray-700"
        >
          <span class="sr-only">{{ item.name }}</span>
          <component :is="item.icon" class="h-6 w-6" aria-hidden="true" />
        </a>
      </div>
      <p class="mt-10 text-center text-xs leading-5">
        &copy; {{ currentYear }} OpenQDA
      </p>
    </div>
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
