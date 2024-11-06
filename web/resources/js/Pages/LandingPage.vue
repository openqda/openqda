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
import LoginForm from './Auth/components/LoginForm.vue';
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
    <LoginForm v-if="!$page.props.auth.user" class="my-5" can-reset-password />
    <div v-else>
      <p class="text-secondary-foreground">You are already logged in.</p>
      <a
        :href="route('projects.index')"
        class="my-12 rounded-full border-2 border-secondary-foreground uppercase font-bold block w-full py-3 text-secondary-foreground dark:text-foreground bg-transparent hover:bg-secondary-foreground hover:text-secondary hover:dark:text-background focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-foreground"
      >
        Show my projects
      </a>
    </div>
  </AuthenticationCard>
</template>
