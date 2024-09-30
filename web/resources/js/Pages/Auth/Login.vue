<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import Checkbox from '../../Components/Checkbox.vue';
import InputError from '../../ui/form/InputError.vue';
import InputLabel from '../../ui/form/InputLabel.vue';
import InputField from '../../ui/form/InputField.vue';

defineProps({
  canResetPassword: Boolean,
  status: String,
  class: String,
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

  <form @submit.prevent="submit" :class="$props.class">
    <div class="my-4">
      <InputLabel for="email" value="Email" class="text-primary-foreground dark:text-foreground" />
      <InputField
        id="email"
        v-model="form.email"
        class="text-primary-foreground placeholder-primary-foreground dark:text-foreground autofill:text-primary-foreground dark:autofill:text-foreground dark:placeholder-foreground border-b-primary-foreground focus:border-b-primary-foreground"
        type="email"
        required
        placeholder="email@email.com"
        autofocus
        autocomplete="email"
      />
      <InputError
        class="email-error mt-2 bg-porsche-500 text-foreground p-1"
        :message="form.errors.email"
      />
    </div>
    <div class="my-4">
      <InputLabel for="password" value="Password"  class="text-primary-foreground dark:text-foreground" />
      <InputField
        id="password"
        v-model="form.password"
        type="password"
        class="text-primary-foreground placeholder-primary-foreground dark:text-foreground dark:placeholder-foreground border-b-primary-foreground focus:border-b-primary-foreground"
        required
        placeholder="your password"
        autocomplete="current-password"
      />
      <InputError
        class="password-error mt-2 bg-red-600 text-foreground"
        :message="form.errors.password"
      />
    </div>
    <div class="flex my-4 justify-between">
      <label class="flex items-center">
        <Checkbox v-model:checked="form.remember" name="remember" />
        <span class="ml-2 text-sm text-primary-foreground text-opacity-60 dark:text-foreground">Remember me</span>
      </label>
      <Link
        v-if="canResetPassword"
        :href="route('password.request')"
        class="hover:underline text-sm text-primary-foreground dark:text-foreground hover:text-opacity-60 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-foreground"
      >
        Forgot password?
      </Link>
    </div>

    <button
      type="submit"
      :disabled="form.processing"
      class="rounded-full border-2 border-primary-foreground uppercase font-bold block w-full py-3 text-secondary-foreground my-5 bg-transparent hover:bg-secondary-foreground hover:text-secondary"
    >
      Log in
    </button>

    <div class="text-left text-primary-foreground mt-5 text-sm">
      <span>Not registered yet?</span>
      <Link
        v-if="canResetPassword"
        :href="route('register')"
        class="underline text-sm hover:text-opacity-60 ml-1 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-foreground"
        >Create an account</Link>
    </div>
  </form>
</template>
