<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Altcha from '@/Components/Altcha.vue';
import Headline2 from '../../Components/layout/Headline2.vue';
import Footer from '../../Layouts/Footer.vue';
import 'altcha';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  altcha: '',
});

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template class="bg-white">
  <Head title="Register" />

  <AuthenticationCard>
    <template #logo>
      <AuthenticationCardLogo />
    </template>

    <Headline2 class="text-center p-2">Early-Access Note</Headline2>

    <blockquote
      class="p-4 my-4 bg-porsche-50 border-l-4 border-porsche-500 text-porsche-900"
    >
      <p class="mb-2 font-semibold">
        Please be aware, that this software is currently in Early-Access.
      </p>
      <ul class="list-disc pl-5 space-y-1">
        <li>You can play around and try out any functionality.</li>
        <li>
          The server might become unavailable, due to updates, maintenance.
        </li>
        <li>
          The database, user-interface, functionality, and set features can
          change at any time and without warning.
        </li>
        <li>You may lose access to any uploaded files.</li>
        <li>Some functionality may not work reliably, yet.</li>
        <li>Your account might become updated, changed, or even deleted.</li>
        <li>
          Please do not upload real interview data without consent. Use an LLM
          to simulate interview data or use public domain text data such as the
          Gutenberg collection.
        </li>
      </ul>

      <p class="mt-2 font-semibold">
        We will notify you, once this software reaches the Pre-Release Stage and
        enters a certain level of reliability.
      </p>
    </blockquote>

    <form @submit.prevent="submit">
      <div>
        <InputLabel for="name" value="Name" />
        <TextInput
          id="name"
          v-model="form.name"
          type="text"
          class="mt-1 block w-full"
          required
          autofocus
          autocomplete="name"
        />
        <InputError class="mt-2" :message="form.errors.name" />
      </div>

      <div class="mt-4">
        <InputLabel for="email" value="Email" />
        <TextInput
          id="email"
          v-model="form.email"
          type="email"
          class="mt-1 block w-full"
          required
          autocomplete="username"
        />
        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <div class="mt-4">
        <InputLabel for="password" value="Password" />
        <TextInput
          id="password"
          v-model="form.password"
          type="password"
          class="mt-1 block w-full"
          required
          autocomplete="new-password"
        />
        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div class="my-4">
        <InputLabel for="password_confirmation" value="Confirm Password" />
        <TextInput
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          class="mt-1 block w-full"
          required
          autocomplete="new-password"
        />
        <InputError class="mt-2" :message="form.errors.password_confirmation" />
      </div>

      <Altcha v-model="form.altcha" />
      <InputError class="mt-2" :message="form.errors.altcha" />

      <div class="flex items-center justify-end mt-4">
        <Link
          :href="route('login')"
          class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cerulean-700"
        >
          Already registered?
        </Link>

        <PrimaryButton
          class="ml-4"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Register
        </PrimaryButton>
      </div>
    </form>
  </AuthenticationCard>
  <Footer />
</template>
