<template>
  <LayoutContainer
    :title="props.title"
    :menu="props.menu"
    :showFooter="props.showFooter"
  >
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
                  <div
                    class="absolute left-full top-0 flex w-12 justify-center pt-5"
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
                  class="flex grow flex-col gap-y-5 overflow-y-auto bg-surface/95 px-6 pb-2 ring-1 ring-white/10 shadow-xs"
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
                    <ul role="list" class="-mx-2 flex-1 flex flex-col gap-4">
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
                    <div class="mt-auto">
                        <Footer />
                    </div>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </Dialog>
      </TransitionRoot>

      <!-- Static sidebar for desktop -->
      <div
        class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 lg:block lg:w-20 lg:overflow-y-auto lg:bg-background lg:pb-4"
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
        <nav class="mt-0 flex flex-col items-stretch">
          <ul
            role="list"
            class="flex flex-col items-center flex flex-col gap-1"
          >
            <li
              v-for="item in navigation"
              :key="item.name"
              :class="item.current ? 'w-full text-center' : ''"
            >
              <Link
                :href="item.href"
                :title="item.label"
                :aria-label="item.label"
                :class="
                  cn(
                    'group flex gap-x-3 rounded-md p-3 text-sm font-semibold leading-6',
                    item.current
                      ? 'w-full justify-center rounded-none text-center bg-surface text-secondary rounded-l-md'
                      : 'text-foreground/50 hover:bg-surface hover:text-foreground/50',
                    item.disabled &&
                      !item.current &&
                      'cursor-not-allowed text-foreground/30 hover:text-foreground/30'
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
              </Link>
              <div
                class="flex items-center justify-center mb-3"
                v-if="usersInRoute(item.href).length"
              >
                <div v-for="user in usersInRoute(item.href)" :key="user.id">
                  <ProfileImage
                    :alt="`Image of ${user.name ?? user.id}`"
                    :name="user.name"
                    :src="user.profile_photo"
                    class="w-3 h-3"
                  />
                </div>
              </div>
            </li>
            <li :class="helpIsActive ? 'w-full text-center' : ''">
              <button
                title="Help, contact, feedback"
                @click="openHelp"
                :class="
                  cn(
                    'group flex gap-x-3 rounded-md p-3 text-sm font-semibold leading-6',
                    helpIsActive
                      ? 'w-full justify-center rounded-none text-center bg-surface text-secondary rounded-l-md'
                      : 'text-foreground/50 hover:bg-surface hover:text-foreground/50'
                  )
                "
              >
                <QuestionMarkCircleIcon
                  class="h-6 w-6 shrink-0"
                  aria-hidden="true"
                />
              </button>
            </li>
          </ul>
          <div
            class="p-3 text-center"
            :title="`Collaboration: ${websocket.status.value}`"
          >
            <SignalIcon
              v-show="false"
              :class="
                cn(
                  'h-5 w-5 mx-auto',
                  websocket.connected.value
                    ? 'text-secondary'
                    : 'text-foreground/50'
                )
              "
            />
          </div>
        </nav>
      </div>

      <!-- sticky top sidebar for mobile -->
      <div
        class="sticky top-0 z-40 flex items-center gap-x-6 bg-surface-l dark:bg-surface-d px-4 py-4 shadow-xs sm:px-6 lg:hidden"
      >
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

      <FlashMessage :flash="$page.props.flash" />
      <HelpDialog />
      <div class="flex lg:pl-20">
        <aside
          v-show="$props.menu !== false"
          class="bg-surface hidden md:w-2/5 xl:w-1/3 2xl:w-1/3 h-screen overflow-y-auto border-background lg:block border-r-background border-r-8 shrink-0 max-w-[50%]"
        >
          <slot name="menu" />
        </aside>

        <main class="h-screen overflow-y-auto bg-surface text-surface-foreground grow">
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
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { cn } from '../utils/css/cn.js';
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue';
import {
  Bars3Icon,
  XMarkIcon,
  SignalIcon,
  QuestionMarkCircleIcon,
} from '@heroicons/vue/24/outline';
import LayoutContainer from './LayoutContainer.vue';
import { NavRoutes } from '../routes/NavRoutes.js';
import { Project } from '../state/Project.js';
import FlashMessage from '../Components/notification/FlashMessage.vue';
import { useWebSocketConnection } from '../collab/useWebSocketConnection.js';
import { useTeam } from '../domain/teams/useTeam.js';
import ProfileImage from '../Components/user/ProfileImage.vue';
import { useConversion } from '../live/useConversion.js';
import { flashMessage } from '../Components/notification/flashMessage.js';
import { useDebug } from '../utils/useDebug.js';
import { useHelpDialog } from '../dialogs/help/useHelpDialog.js';
import HelpDialog from '../dialogs/help/HelpDialog.vue';
import Footer from './Footer.vue'

const websocket = useWebSocketConnection();
const navigation = ref([]);
const sidebarOpen = ref(false);
const {
  initTeams,
  dispatchPresence,
  teamInitialized,
  hasTeam,
  dispose,
  teamId,
  usersInChannel,
} = useTeam();
websocket.initWebSocket();
const debug = useDebug({ scope: 'nav' });
const { isActive: helpIsActive, open: openHelp } = useHelpDialog();

const props = defineProps({
  title: String,
  menu: {
    type: Boolean,
    required: false,
  },
  showFooter: {
    type: Boolean,
    required: false,
  },
  sources: {
    type: Array,
    required: false,
  },
});

let pingIntervalId;
let fails = 0;
const cleanup = () => {
  clearInterval(pingIntervalId);
  dispose();
};

const setupTeam = () => {
  if (teamInitialized(teamId.value)) {
    return;
  }
  initTeams();

  if (pingIntervalId) {
    clearInterval(pingIntervalId);
  }
  dispatchPresence().catch(console.error);
  pingIntervalId = setInterval(async () => {
    // Dispatch an event to the server to indicate that the user is still on the page
    // This could be done via an Axios call to a specific endpoint that handles this logic
    const { response, error } = await dispatchPresence();
    if (error || response.status >= 400) {
      console.error(error ?? response);
      fails++;
    }
    if (fails >= 2) return clearInterval(pingIntervalId);
  }, 5000); // Every 5 seconds
  document.addEventListener('beforeunload', cleanup);
};
onMounted(() => {
  const projectId = Project.getId();
  const active = route().current();

  if (projectId && sessionStorage.getItem('projectId') !== projectId) {
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

  if (hasTeam) {
    watch(teamId, setupTeam, { immediate: true });
  }

  const { onConversionComplete, onConversionFailed } = useConversion({
    projectId,
  });
  onConversionFailed((e) => {
    const { sourceId } = e;
    let message = 'Conversion of a file failed!';
    const file =
      sourceId && props.sources && props.sources.find((s) => s.id === sourceId);
    if (file) {
      message = `Conversion of ${file.name} failed!`;
    }
    flashMessage(message, { type: 'error ' });
  });
  onConversionComplete((e) => {
    const { sourceId } = e;
    let message = 'Conversion of a file successful!';
    const file =
      sourceId && props.sources && props.sources.find((s) => s.id === sourceId);
    if (file) {
      message = `Conversion of ${file.name} successful!`;
    }
    flashMessage(message, { type: 'success ' });
  });
});

const team = ref([]);
watch(
  usersInChannel,
  (value) => {
    debug('changed users in channel', value);
    team.value = Object.values(value ?? {});
  },
  { deep: true }
);

const usersInRoute = (href = '') => {
  debug('usersInRoute', href, team.value);
  return team.value.filter((user) => {
    const url = user.url ?? '';
    return url && href && (href.includes(url) || url.includes(href));
  });
};

onUnmounted(() => {
  // Leave the channel when the component is unmounted
  if (hasTeam) {
    document.removeEventListener('beforeunload', cleanup);
    cleanup();
  }
});
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
