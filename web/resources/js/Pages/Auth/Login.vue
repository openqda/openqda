<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import InputError from '../../form/InputError.vue';
import InputLabel from '../../form/InputLabel.vue';
import InputField from '../../form/InputField.vue';

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

  <form @submit.prevent="submit" :class="$props.class" class="space-y-10">
    <div>
      <InputLabel
        for="email"
        value="Email"
        class="text-secondary-foreground dark:text-foreground"
      />
      <InputField
        id="email"
        v-model="form.email"
        class="text-secondary-foreground placeholder-secondary-foreground/50 dark:text-foreground autofill:text-secondary-foreground dark:autofill:text-foreground dark:placeholder-foreground border-b-secondary-foreground focus:border-b-secondary-foreground"
        type="email"
        required
        placeholder="Your account's Email"
        autofocus
        autocomplete="email"
      />
      <InputError
        class="email-error mt-2 bg-porsche-500 text-foreground p-1"
        :message="form.errors.email"
      />
    </div>
    <div>
      <InputLabel
        for="password"
        value="Password"
        class="text-secondary-foreground dark:text-foreground"
      />
      <InputField
        id="password"
        v-model="form.password"
        type="password"
        class="text-secondary-foreground placeholder-secondary-foreground/50 dark:text-foreground dark:placeholder-foreground border-b-secondary-foreground focus:border-b-secondary-foreground"
        required
        placeholder="Your account's password"
        autocomplete="current-password"
      />
      <InputError
        class="password-error mt-2 bg-red-600 text-foreground"
        :message="form.errors.password"
      />
    </div>

    <button
      type="submit"
      :disabled="form.processing"
      class="my-12 rounded-full border-2 border-secondary-foreground uppercase font-bold block w-full py-3 text-secondary-foreground dark:text-foreground bg-transparent hover:bg-secondary-foreground hover:text-secondary hover:dark:text-background focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-foreground"
    >
      Log in
    </button>

    <div class="flex justify-between items-center">
      <!-- TODO make this active when working -->
      <!--
            <label class="flex items-center" v-show="false">
                <Checkbox v-model:checked="form.remember" name="remember" />
                <span class="ml-2 text-sm text-secondary-foreground text-opacity-60 dark:text-foreground">Remember me</span>
            </label>
            -->
      <div class="text-left text-secondary-foreground text-sm">
        <span class="mr-2">Not registered yet?</span>
        <Link
          v-if="canResetPassword"
          :href="route('register')"
          class="hover:underline text-sm text-secondary-foreground dark:text-foreground hover:text-opacity-60 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-foreground"
          >Create an account
        </Link>
      </div>
      <Link
        v-if="canResetPassword"
        :href="route('password.request')"
        class="hover:underline text-sm text-secondary-foreground dark:text-foreground hover:text-opacity-60 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-foreground"
      >
        Forgot password?
      </Link>
    </div>
  </form>
</template>
