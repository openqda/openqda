<script setup>
import { ArrowPathIcon } from '@heroicons/vue/24/solid/index.js';
import Link from '../../Components/Link.vue';
import Button from '../../Components/interactive/Button.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticationCard from '../../Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import { ref } from 'vue';
import Headline1 from '../../Components/layout/Headline1.vue';

const props = usePage().props;
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
    <div
      class="text-foreground text-start w-full bg-surface rounded shadow-lg p-4"
    >
      <Headline1 class="font-semibold text-center my-4"
        >Thank you for your registration!</Headline1
      >
      <div class="mb-4 text-sm">
        Before you continue, please verify your email address by clicking on the
        link we just emailed to
        <span class="font-semibold">{{ props?.auth?.user?.email }}</span>
      </div>
      <div class="my-1"></div>
      <div class="mb-4 text-sm">
        If you did not receive the email, we will gladly send you another one.
        Click on "Edit Profile" to change the email address, in case you made a
        typo during registration.
      </div>

      <div
        v-if="verificationLinkSent"
        class="mb-4 font-medium text-sm text-confirmative"
      >
        A new verification link has been sent to the email address you provided
        in your profile settings.
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

          <div class="flex flex-row gap-4">
            <Link :href="route('profile.show')"> Edit Profile</Link>

            <Link :href="route('logout')" method="post" as="button">
              Log Out
            </Link>
          </div>
        </div>
      </form>
    </div>
  </AuthenticationCard>
</template>
