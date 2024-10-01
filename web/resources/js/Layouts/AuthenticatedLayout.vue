<template>
  <LayoutContainer v-bind="$props" :showFooter="$props.showFooter">
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
            <div class="fixed inset-0 bg-background/80" />
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
              <DialogPanel class="relative mr-16 flex w-full flex-1">
                <TransitionChild
                  as="template"
                  enter="ease-in-out duration-300"
                  enter-from="opacity-0"
                  enter-to="opacity-100"
                  leave="ease-in-out duration-300"
                  leave-from="opacity-100"
                  leave-to="opacity-0"
                >
                  <div class="absolute left-full top-0 flex w-12 justify-center pt-5">
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
                <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-surface/95 px-6 pb-2 ring-1 ring-white/10 shadow-sm">
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
                    <slot name="menu"></slot>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </Dialog>
      </TransitionRoot>

      <!-- Static sidebar for desktop -->
      <div class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 lg:block lg:w-20 lg:overflow-y-auto lg:bg-background lg:pb-4">
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
              :class="item.current ? 'w-full text-center' : ''"
            >
              <a
                :href="item.href"
                :title="item.label"
                :aria-label="item.label"
                :class="
                  cn(
                    'group flex gap-x-3 rounded-md p-3 text-sm font-semibold leading-6',
                    item.current
                      ? 'w-full justify-center rounded-none text-center bg-surface text-secondary rounded-l-md'
                      : 'text-foreground/50 hover:bg-surface hover:text-foreground/50',
                    item.disabled && !item.current && 'cursor-not-allowed'
                  )
                "
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
          </ul>
        </nav>
      </div>

      <!-- sticky top sidebar for mobile -->
      <div
        class="sticky top-0 z-40 flex items-center gap-x-6 bg-surface-l dark:bg-surface-d px-4 py-4 shadow-sm sm:px-6 lg:hidden">
        <button
          type="button"
          class="-m-2.5 p-2.5 text-foreground/50 lg:hidden"
          @click="sidebarOpen = true"
        >
          <span class="sr-only">Open Menu</span>
          <Bars3Icon class="h-6 w-6" aria-hidden="true" />
        </button>
        <div class="flex-1 text-sm font-semibold leading-6 text-primary">
          {{ $props.title }}
        </div>
      </div>

      <FlashMessage
        v-if="$page.props.flash.message"
        :flash="$page.props.flash"
      />

      <div class="flex lg:pl-20">
        <aside
          v-show="$props.menu !== false"
          class="bg-surface hidden lg:w-full xl:w-1/2 2xl:w-1/3 h-screen overflow-y-auto border-background px-1 sm:px-2 lg:px-3 lg:block border-r-background border-r-8"
        >
          <h1
            v-if="$props.title"
            class="font-extrabold text-xl text-primary dark:text-foreground"
          >
            {{ $props.title }}
          </h1>
          <slot name="menu" />
        </aside>

        <main
          :class="
            cn(
              'h-screen overflow-y-auto bg-surface text-surface-foreground flex-grow'
            )
          "
        >
          <Transition>
            <slot name="main" />
          </Transition>
        </main>
      </div>
    </div>
  </LayoutContainer>
</template>

<script setup>
import { Routes } from '../routes/Routes.js';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { cn } from '../utils/css/cn.js';

defineProps({
  title: String,
  menu: {
    type: Boolean,
    required: false,
  },
  showFooter: {
    type: Boolean,
    required: false,
  },
});
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue';
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';
import LayoutContainer from './LayoutContainer.vue';
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
    const current = route.active(active);
    const label = disabled
      ? `${route.label} - You need to select a project`
      : route.label;
    return { icon, ...route, href, disabled, count, current, label };
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
