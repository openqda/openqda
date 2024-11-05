<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '../../Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '../../Components/AuthenticationCardLogo.vue';
import InputError from '../../form/InputError.vue';
import InputLabel from '../../form/InputLabel.vue';
import PrimaryButton from '../../Components/PrimaryButton.vue';
import InputField from '../../form/InputField.vue';
import Footer from '../../Layouts/Footer.vue';
import Headline2 from '../../Components/layout/Headline2.vue';
import Headline1 from '../../Components/layout/Headline1.vue';

defineProps({
  status: String,
});

const form = useForm({
  email: '',
});

const submit = () => {
  form.post(route('password.email'));
};
</script>

<template>
  <AuthenticationCard title="Forgot Password">
    <Headline1 class="text-center p-2 my-6 text-secondary-foreground">
      Reset your Password
    </Headline1>

    <div class="text-sm text-secondary-foreground my-6">
      Forgot your password? No problem. Just let us know your email address and
      we will email you a password reset link that will allow you to choose a
      new one.
    </div>

    <div v-if="status" class="text-sm text-secondary-foreground">
      {{ status }}
    </div>

    <form @submit.prevent="submit" class="space-y-10">
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
        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <button
        type="submit"
        class="my-12 rounded-full border-2 border-secondary-foreground uppercase font-bold block w-full py-3 text-secondary-foreground dark:text-foreground bg-transparent hover:bg-secondary-foreground hover:text-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-foreground"
        :disabled="form.processing"
      >
        Email Password Reset Link
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
            :href="route('register')"
            class="hover:underline text-sm text-secondary-foreground dark:text-foreground hover:text-opacity-60 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-foreground"
            >Create an account</Link
          >
        </div>
        <span class="text-secondary-foreground">
          <span class="me-1">Already registered?</span>
          <Link
            href="/"
            class="hover:underline text-sm text-secondary-foreground dark:text-foreground hover:text-opacity-60 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-foreground"
          >
            Log in
          </Link>
        </span>
      </div>
    </form>
  </AuthenticationCard>
  <Footer />
</template>
