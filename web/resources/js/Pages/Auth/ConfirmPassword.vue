<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '../../Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '../../Components/AuthenticationCardLogo.vue';
import InputError from '../../form/InputError.vue';
import InputLabel from '../../form/InputLabel.vue';
import PrimaryButton from '../../Components/PrimaryButton.vue';
import InputField from '../../form/InputField.vue';
import Footer from '../../Layouts/Footer.vue';

const form = useForm({
  password: '',
});

const passwordInput = ref(null);

const submit = () => {
  form.post(route('password.confirm'), {
    onFinish: () => {
      form.reset();

      passwordInput.value.focus();
    },
  });
};
</script>

<template>
  <Head title="Secure Area" />

  <AuthenticationCard>
    <template #logo>
      <AuthenticationCardLogo />
    </template>

    <div class="mb-4 text-sm text-gray-600">
      This is a secure area of the application. Please confirm your password
      before continuing.
    </div>

    <form @submit.prevent="submit">
      <div>
        <InputLabel for="password" value="Password" />
        <InputField
          id="password"
          ref="passwordInput"
          v-model="form.password"
          type="password"
          class="mt-1 block w-full"
          required
          autocomplete="current-password"
          autofocus
        />
        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div class="flex justify-end mt-4">
        <PrimaryButton
          class="ml-4"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Confirm
        </PrimaryButton>
      </div>
    </form>
  </AuthenticationCard>
  <Footer />
</template>
