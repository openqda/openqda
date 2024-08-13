<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import Checkbox from '../../Components/Checkbox.vue';
import InputError from '../../ui/form/InputError.vue';
import InputLabel from '../../ui/form/InputLabel.vue';
import PrimaryButton from '../../Components/PrimaryButton.vue';
import InputField from '../../ui/form/InputField.vue';

defineProps({
  canResetPassword: Boolean,
  status: String,
});

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form
    .transform((data) => ({
      ...data,
      remember: form.remember ? 'on' : '',
    }))
    .post(route('login'), {
      onFinish: () => form.reset('password'),
    });
};
</script>

<template>
  <div
    v-if="status"
    class="status-message mb-4 font-medium text-sm text-green-600"
  >
    {{ status }}
  </div>

  <form @submit.prevent="submit">
    <InputLabel
      for="email"
      value="email"
      class="text-white uppercase font-thin"
    />
    <InputField
      id="email"
      v-model="form.email"
      type="email"
      class="mt-1 block w-full bg-transparent border-l-0 border-r-0 border-t-0 border-b focus:outline-none focus:ring focus:border-white focus:border-l-0 focus:border-r-0 focus:border-t-0 focus:border-b-2 rounded-none"
      required
      autofocus
      autocomplete="username"
    />
    <InputError class="email-error mt-2" :message="form.errors.email" />

    <div class="relative h-11 w-full min-w-[200px]">
      <input
        placeholder="EMail"
        class="peer h-full w-full border-b border-blue-gray-200 bg-transparent pt-4 pb-1.5 font-sans text-sm font-normal text-blue-gray-700 outline outline-0 transition-all placeholder-shown:border-blue-gray-200 focus:border-gray-500 focus:outline-0 disabled:border-0 disabled:bg-blue-gray-50 placeholder:opacity-0 focus:placeholder:opacity-100"
      />
      <label
        class="after:content[''] pointer-events-none absolute left-0 -top-1.5 flex h-full w-full select-none !overflow-visible truncate text-[11px] font-normal leading-tight text-gray-500 transition-all after:absolute after:-bottom-1.5 after:block after:w-full after:scale-x-0 after:border-b-2 after:border-gray-500 after:transition-transform after:duration-300 peer-placeholder-shown:text-sm peer-placeholder-shown:leading-[4.25] peer-placeholder-shown:text-blue-gray-500 peer-focus:text-[11px] peer-focus:leading-tight peer-focus:text-gray-900 peer-focus:after:scale-x-100 peer-focus:after:border-gray-900 peer-disabled:text-transparent peer-disabled:peer-placeholder-shown:text-blue-gray-500"
      >
        Email
      </label>
    </div>

    <div class="mt-4">
      <InputLabel for="password" value="Password" />
      <InputField
        id="password"
        v-model="form.password"
        type="password"
        class="mt-1 block w-full"
        required
        autocomplete="current-password"
      />
      <InputError class="password-error mt-2" :message="form.errors.password" />
    </div>
    <div class="block mt-4">
      <label class="flex items-center">
        <Checkbox v-model:checked="form.remember" name="remember" />
        <span class="ml-2 text-sm text-gray-600">Remember me</span>
      </label>
    </div>

    <div class="flex items-center justify-end mt-4">
      <Link
        v-if="canResetPassword"
        :href="route('password.request')"
        class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cerulean-700"
      >
        Forgot your password?
      </Link>

      <PrimaryButton
        class="ml-4"
        :class="{ 'opacity-25': form.processing }"
        :disabled="form.processing"
      >
        Log in
      </PrimaryButton>
    </div>
  </form>
</template>
