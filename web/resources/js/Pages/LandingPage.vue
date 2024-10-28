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
  </AuthenticationCard>
</template>
