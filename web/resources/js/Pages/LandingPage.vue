<script setup>
/*
 |--------------------------------------------------------------------------
 | Landing Page
 |--------------------------------------------------------------------------
 | This is where users are supposed to land,
 | when not logged in or registered
 */
//import { router } from '@inertiajs/vue3';
import LayoutContainer from '../Layouts/LayoutContainer.vue';
import Login from './Auth/Login.vue';

defineProps({
  background: String,
  canLogin: Boolean,
  canRegister: Boolean,
  laravelVersion: String,
  phpVersion: String,
});

// function onLogout() {
//   router.post(route('logout'));
// }
</script>

<template>
  <LayoutContainer :title="$page.props.slogan">
    <div class="grid grid-cols-1 md:grid-cols-2 items-stretch min-h-screen">
      <div class="left flex-1 bg-gradient-to-b from-primary-l to-secondary-l">
        <div class="p-0 m-0 md:p-5 md:m-5 text-center">
          <img
            :src="$page.props.logo"
            class="w-full sm:w-3/4 md:w-1/2 lg:w-1/4 xl:w-1/5 ml-auto mr-auto"
          />
          <h1 class="font-extrabold text-6xl text-white head-shadow uppercase">
            {{ $page.props.title }}
          </h1>

          <Login v-if="!$page.props.auth.user" />
        </div>
      </div>
      <div class="right flex-1 bg-background-l dark:bg-background-d">moo</div>
    </div>
  </LayoutContainer>
</template>

<style scoped>
.head-shadow {
  text-shadow:
    1px 1px 1px black,
    0 0 10px white,
    0 0 1px black;
}
</style>
