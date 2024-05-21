<script setup>
/*
 |--------------------------------------------------------------------------
 | Landing Page
 |--------------------------------------------------------------------------
 | This is where users are supposed to land,
 | when not logged in or registered
 */
import { Head, router } from '@inertiajs/vue3';
import Footer from '../Layouts/Footer.vue';

defineProps({
  background: String,
  canLogin: Boolean,
  canRegister: Boolean,
  laravelVersion: String,
  phpVersion: String,
});

function onLogout() {
  router.post(route('logout'));
}
</script>

<template>
  <div class="">
    <Head title="Welcome" />
    <div
      :style="{
        backgroundImage: `url('${$page.props.bgtl}') ,url('${$page.props.bgtr}'), url('${$page.props.bgbl}') ,url('${$page.props.bgbr}')`,
        backgroundPosition: 'left top, right top, left bottom, right bottom',
        backgroundSize: '20%',
        backgroundAttachment: 'fixed',
      }"
      class="relative min-h-screen bg-silver-50 bg-center bg-no-repeat bg-contain sm:flex sm:justify-center sm:items-center bg-dots-darker selection:bg-red-700 selection:text-white"
    >
      <div class="relative px-6 isolate pt-14 lg:px-8">
        <div class="relative isolate pt-14">
          <div class="py-24 sm:py-32 lg:pb-40">
            <div class="px-6 mx-auto max-w-7xl lg:px-8">
              <div
                class="max-w-2xl mx-auto text-center bg-white p-5 shadow-2xl rounded-lg border border-silver-300"
              >
                <div
                  class="relative px-3 py-1 text-sm leading-6 text-gray-500 rounded-full ring-1 ring-gray-900/10 hover:ring-gray-900/20"
                >
                  {{ $page.props.description }}
                </div>
                <h1
                  class="text-4xl font-bold tracking-tight sm:text-6xl text-cerulean-700"
                >
                  {{ $page.props.title }}
                </h1>
                <p class="mt-6 text-lg leading-8 text-black">
                  {{ $page.props.slogan }}
                </p>
                <img
                  :src="$page.props.logo"
                  class="w-full sm:w-3/4 md:w-1/2 lg:w-1/4 xl:w-1/5 ml-auto mr-auto"
                />
                <div
                  v-if="!$page.props.auth.user"
                  class="flex items-center justify-center mt-10 gap-x-6"
                >
                  <a
                    :href="route('login')"
                    class="rounded-md bg-cerulean-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-cerulean-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-500"
                    >Sign in</a
                  >
                  <a
                    :href="route('register')"
                    class="rounded-md bg-porsche-400 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-porsche-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-porsche-400"
                    >Register</a
                  >
                </div>
                <div
                  v-else-if="$page.props.auth.user"
                  class="flex items-center justify-center mt-10 gap-x-6"
                >
                  <a
                    :href="route('projects.index')"
                    class="rounded-md bg-cerulean-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-cerulean-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-500"
                    >Projects</a
                  >
                  <form @submit.prevent="logout" class="cursor-pointer">
                    <a
                      @click="onLogout"
                      class="rounded-md bg-cerulean-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-cerulean-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-500"
                      >Log out</a
                    >
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <Footer />
</template>

<style>
.bg-dots-darker {
  background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(0,0,0,0.07)'/%3E%3C/svg%3E");
}

@media (prefers-color-scheme: dark) {
  .dark\:bg-dots-lighter {
    background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(255,255,255,0.07)'/%3E%3C/svg%3E");
  }
}
</style>
