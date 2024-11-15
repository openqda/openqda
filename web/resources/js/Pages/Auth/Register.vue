<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '../../Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '../../Components/AuthenticationCardLogo.vue';
import InputError from '../../form/InputError.vue';
import InputLabel from '../../form/InputLabel.vue';
import InputField from '../../form/InputField.vue';
import Altcha from '../../Components/Altcha.vue';
import 'altcha';
import Button from '../../Components/interactive/Button.vue';
import Headline1 from '../../Components/layout/Headline1.vue';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  altcha: '',
});
const submit = () => {
  const reset = () => form.reset('password', 'password_confirmation');
  form.post(route('register'), {
    onFinish: reset,
  });
};
</script>

<template class="bg-white">
  <Head title="Register" />

  <AuthenticationCard>
    <template #logo>
      <AuthenticationCardLogo />
    </template>

    <Headline1 class="text-center p-2 my-6 text-secondary-foreground">
      Register an Account
    </Headline1>

    <form @submit.prevent="submit" class="space-y-10">
      <div>
        <InputLabel
          for="name"
          value="Name"
          class="text-secondary-foreground dark:text-foreground"
        />
        <InputField
          id="name"
          v-model="form.name"
          type="text"
          placeholder="Provide your name"
          class="text-secondary-foreground placeholder-secondary-foreground/50 dark:text-foreground autofill:text-secondary-foreground dark:autofill:text-foreground dark:placeholder-foreground border-b-secondary-foreground focus:border-b-secondary-foreground"
          required
          autofocus
          autocomplete="off"
        />
        <InputError class="mt-2" :message="form.errors.name" />
      </div>

      <div>
        <InputLabel
          for="email"
          value="Email"
          class="text-secondary-foreground dark:text-foreground"
        />
        <InputField
          id="email"
          v-model="form.email"
          type="email"
          class="text-secondary-foreground placeholder-secondary-foreground/50 dark:text-foreground autofill:text-secondary-foreground dark:autofill:text-foreground dark:placeholder-foreground border-b-secondary-foreground focus:border-b-secondary-foreground"
          required
          placeholder="Provide a valid Email address"
          autocomplete="off"
        />
        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <div class="mt-6">
        <InputLabel
          for="password"
          value="Password"
          class="text-secondary-foreground dark:text-foreground"
        />
        <InputField
          id="password"
          v-model="form.password"
          type="password"
          placeholder="Minimum 8 characters"
          class="text-secondary-foreground placeholder-secondary-foreground/50 dark:text-foreground autofill:text-secondary-foreground dark:autofill:text-foreground dark:placeholder-foreground border-b-secondary-foreground focus:border-b-secondary-foreground"
          required
          autocomplete="off"
        />
        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div>
        <InputLabel
          for="password_confirmation"
          value="Confirm Password"
          class="text-secondary-foreground dark:text-foreground"
        />
        <InputField
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          placeholder="Must be same as password"
          class="text-secondary-foreground placeholder-secondary-foreground/50 dark:text-foreground autofill:text-secondary-foreground dark:autofill:text-foreground dark:placeholder-foreground border-b-secondary-foreground focus:border-b-secondary-foreground"
          required
          autocomplete="off"
        />
        <InputError class="mt-2" :message="form.errors.password_confirmation" />
      </div>

      <div>
        <InputLabel
          for="password_confirmation"
          value="Checking, if you are a human"
          class="text-secondary-foreground dark:text-foreground"
        />
        <Altcha v-model="form.altcha" />
        <InputError class="mt-2" :message="form.errors.altcha" />
      </div>

      <div class="flex items-center justify-between text-sm">
        <span class="text-secondary-foreground">
          <span class="me-1">Already registered?</span>
          <Link
            href="/"
            class="hover:underline text-sm text-secondary-foreground dark:text-foreground hover:text-opacity-60 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-foreground"
          >
            Log in
          </Link>
        </span>

        <Button
          type="submit"
          class="rounded-full border-2 border-secondary-foreground uppercase font-bold ms-6 py-3 text-secondary-foreground my-5 bg-transparent hover:bg-secondary-foreground hover:text-secondary"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Register
        </Button>
      </div>
    </form>
  </AuthenticationCard>
</template>
<style scoped>
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0 30px white inset !important;
}
</style>
