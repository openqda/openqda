<script setup>
/*
 |--------------------------------------------------------------------------
 | Landing Page
 |--------------------------------------------------------------------------
 | This is where users are supposed to land,
 | when not logged in or registered.
 | Authenticated users should be redirected to
 | the projects list
 */
import Login from './Auth/Login.vue';
import { Link } from '@inertiajs/vue3';
import AuthenticationCard from '../Components/AuthenticationCard.vue';

defineProps({
  background: String,
  canLogin: Boolean,
  canRegister: Boolean,
  laravelVersion: String,
  phpVersion: String,
});
</script>

<template>
  <AuthenticationCard>
    <Login v-if="!$page.props.auth.user" class="my-5" can-reset-password />
    <Link
      :href="route('projects.index')"
      class="rounded-full border-2 border-white uppercase font-bold block w-full p-3 text-white my-5 bg-transparent hover:bg-white hover:text-primary"
    >
      My Projects
    </Link>
  </AuthenticationCard>
</template>
