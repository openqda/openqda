<template>
  <LayoutContainer v-bind="$props">
    <div class="h-full">
      <TransitionRoot as="template" :show="sidebarOpen">
        <Dialog class="relative z-50 lg:hidden" @close="sidebarOpen = false">
          <TransitionChild
            as="template"
            enter="transition-opacity ease-linear duration-300"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="transition-opacity ease-linear duration-300"
            leave-from="opacity-100"
            leave-to="opacity-0"
          >
            <div class="fixed inset-0 bg-gray-900/80" />
          </TransitionChild>

          <div class="fixed inset-0 flex">
            <TransitionChild
              as="template"
              enter="transition ease-in-out duration-300 transform"
              enter-from="-translate-x-full"
              enter-to="translate-x-0"
              leave="transition ease-in-out duration-300 transform"
              leave-from="translate-x-0"
              leave-to="-translate-x-full"
            >
              <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
                <TransitionChild
                  as="template"
                  enter="ease-in-out duration-300"
                  enter-from="opacity-0"
                  enter-to="opacity-100"
                  leave="ease-in-out duration-300"
                  leave-from="opacity-100"
                  leave-to="opacity-0"
                >
                  <div
                    class="absolute left-full top-0 flex w-16 justify-center pt-5"
                  >
                    <button
                      type="button"
                      class="-m-2.5 p-2.5"
                      @click="sidebarOpen = false"
                    >
                      <span class="sr-only">Close sidebar</span>
                      <XMarkIcon
                        class="h-6 w-6 text-label-d"
                        aria-hidden="true"
                      />
                    </button>
                  </div>
                </TransitionChild>

                <div
                  class="flex grow flex-col gap-y-5 overflow-y-auto bg-surface-l dark:bg-surface-d px-6 pb-2 ring-1 ring-white/10 shadow-sm"
                >
                  <div class="flex h-16 shrink-0 items-center">
                    <Link
                      :href="Routes.projects.path()"
                      preserve-state
                      :title="Routes.projects.label"
                    >
                      <img
                        class="h-8 w-auto"
                        :src="$page.props.logo"
                        :alt="Routes.projects.label"
                      />
                    </Link>
                  </div>
                  <nav class="flex flex-1 flex-col">
                    <ul role="list" class="-mx-2 flex-1 space-y-1">
                      <li v-for="item in navigation" :key="item.name">
                        <a
                          :href="item.href"
                          :title="item.label"
                          :class="[
                            item.current
                              ? 'text-secondary-l dark:text-secondary-d'
                              : 'text-passive-l hover:bg-gray-800 hover:text-white',
                            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                          ]"
                          :aria-disabled="item.disabled"
                        >
                          <component
                            :is="item.icon"
                            class="h-6 w-6 shrink-0"
                            aria-hidden="true"
                          />
                          {{ item.label }}
                        </a>
                      </li>
                    </ul>
                  </nav>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </Dialog>
      </TransitionRoot>

      <!-- Static sidebar for desktop -->
      <div
        class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 lg:block lg:w-20 lg:overflow-y-auto lg:bg-background-l dark:lg:bg-background-d lg:pb-4"
      >
        <div class="flex h-16 shrink-0 items-center justify-center">
          <Link
            :href="Routes.projects.path()"
            preserve-state
            :title="Routes.projects.label"
          >
            <img
              class="h-8 w-auto"
              :src="$page.props.logo"
              :alt="Routes.project.label"
            />
          </Link>
        </div>
        <nav class="mt-0">
          <ul role="list" class="flex flex-col items-center space-y-1">
            <li
              v-for="item in navigation"
              :key="item.name"
              :class="item.current ? 'w-full' : ''"
            >
              <a
                :href="item.href"
                :title="item.label"
                :aria-label="item.label"
                :class="[
                  item.current
                    ? 'rounded-none text-center bg-surface-l text-secondary-l dark:bg-surface-d dark:text-secondary-d'
                    : 'text-passive-l dark:text-passive-d hover:bg-secondary-l dark:hover:bg-secondary-d hover:text-label-d dark:hover:text-label-l',
                  'group flex gap-x-3 rounded-md p-3 text-sm font-semibold leading-6',
                ]"
                :aria-disabled="item.disabled"
              >
                <component
                  :is="item.icon"
                  class="h-6 w-6 shrink-0"
                  aria-hidden="true"
                />
                <span class="sr-only">{{ item.label }}</span>
              </a>
            </li>
            <li>
              <ThemeSwitch
                light="w-6 h-6 text-dark"
                dark="w-6 h-6 text-white"
              />
            </li>
          </ul>
        </nav>
      </div>

      <!-- sticky top sidebar for mobile -->
      <div
        class="sticky top-0 z-40 flex items-center gap-x-6 bg-surface-l dark:bg-surface-d px-4 py-4 shadow-sm sm:px-6 lg:hidden"
      >
        <button
          type="button"
          class="-m-2.5 p-2.5 text-label-l dark:text-label-d lg:hidden"
          @click="sidebarOpen = true"
        >
          <span class="sr-only">Open sidebar</span>
          <Bars3Icon class="h-6 w-6" aria-hidden="true" />
        </button>
        <div
          class="flex-1 text-sm font-semibold leading-6 text-primary-l dark:text-primary-d"
        >
          {{ $props.title }}
        </div>
        <ThemeSwitch light="w-6 h-6 text-dark" dark="w-6 h-6 text-white" />
      </div>

      <FlashMessage
        v-if="$page.props.flash.message"
        :flash="$page.props.flash"
      />
      <aside
        class="fixed inset-y-0 left-20 hidden w-96 bg-surface-l dark:bg-surface-d overflow-y-auto border-r border-gray-200 px-4 py-6 sm:px-6 lg:px-8 lg:block border-r-background-l border-r-4"
      >
        <h1 class="font-extrabold text-xl text-primary-l dark:text-white">
          {{ $props.title }}
        </h1>
        <slot name="menu" />
      </aside>

      <main class="lg:pl-20 h-full">
        <div class="xl:pl-96 h-full">
          <div class="px-4 py-2 sm:px-6 lg:px-8 lg:py-0 h-full">
            <Transition>
              <slot name="main" />
            </Transition>
          </div>
        </div>
      </main>
    </div>
  </LayoutContainer>
</template>

<script setup>
import { Routes } from '../routes/Routes.js';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
defineProps({
  title: String,
});
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue';
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';
import LayoutContainer from './LayoutContainer.vue';
import ThemeSwitch from '../ui/theme/ThemeSwitch.vue';
import { NavRoutes } from '../routes/NavRoutes.js';
import { Project } from '../state/Project.js';
import FlashMessage from '../Components/notification/FlashMessage.vue';

onMounted(() => {
  const projectId = Project.getId();
  const active = route().current();

  if (projectId) {
    // we should update the projectId only of it's
    // not in session storage
    sessionStorage.setItem('projectId', projectId);
  }

  const routes = NavRoutes.map(({ icon, route }) => {
    const href = route.path(projectId);
    const disabled = !href;
    const count = 0;
    const current = active === route.key;
    return { icon, href, ...route, disabled, count, current };
  });

  navigation.value.push(...routes);
});

const navigation = ref([]);
const sidebarOpen = ref(false);
</script>
<style scoped>
/* we will explain what these classes do next! */
.v-enter-active,
.v-leave-active {
  transition: opacity 0.3s ease;
}

.v-enter-from,
.v-leave-to {
  opacity: 0;
}
</style>
