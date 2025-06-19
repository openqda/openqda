<script setup>
import { ArrowPathIcon } from '@heroicons/vue/24/solid/index.js';
import Link from '../../Components/Link.vue';
import Button from '../../Components/interactive/Button.vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '../../Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import { ref } from 'vue';

const form = useForm({});
const verificationLinkSent = ref(false);
const submit = () => {
  form.post(route('verification.send'), {
    onSuccess: () => (verificationLinkSent.value = true),
  });
};
</script>

<template>
  <Head title="Email Verification" />

  <AuthenticationCard>
    <template #logo>
      <AuthenticationCardLogo />
    </template>

    <div class="mb-4 text-sm text-foreground">
      Before continuing, could you verify your email address by clicking on the
      link we just emailed to you? If you didn't receive the email, we will
      gladly send you another.
    </div>

    <div
      v-if="verificationLinkSent"
      class="mb-4 font-medium text-sm text-confirmative"
    >
      A new verification link has been sent to the email address you provided in
      your profile settings.
    </div>

    <form v-else @submit.prevent="submit">
      <div class="mt-4 flex items-center justify-between">
        <ArrowPathIcon v-if="form.processing" class="w-4 h-4 animate-spin" />
        <Button
          data-id="sbumitbtn"
          type="submit"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Resend Verification Email
        </Button>

        <div class="flex flex-row gap-2">
          <Link :href="route('profile.show')"> Edit Profile</Link>

          <Link :href="route('logout')" method="post" as="button">
            Log Out
          </Link>
        </div>
      </div>
    </form>
  </AuthenticationCard>
</template>
