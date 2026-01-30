<script setup>
import DeleteUserForm from '../../Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '../../Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import UpdatePasswordForm from '../../Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '../../Pages/Profile/Partials/UpdateProfileInformationForm.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import InputLabel from '../../form/InputLabel.vue';
import ThemeSwitch from '../../theme/ThemeSwitch.vue';
import Button from '../../Components/interactive/Button.vue';
import { router } from '@inertiajs/vue3';
import BaseContainer from '../../Layouts/BaseContainer.vue';
import LegalForm from './Partials/LegalForm.vue';
import { Theme } from '../../theme/Theme.js';
// import '../../Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';

defineProps({
  confirmsTwoFactorAuthentication: Boolean,
  sessions: Array,
});

function onLogout() {
  // Perform logout and reset theme after it completes
  router.post(
    route('logout'),
    {},
    {
      onSuccess: () => {
        // Reset theme to default 'light' after logout completes
        Theme.update('light');
      },
    }
  );
}
</script>

<template>
  <AuthenticatedLayout :menu="false">
    <template #main>
      <BaseContainer class="w-full lg:w-3/4 xl:w-1/2 p-1 lg:p-3">
        <form @submit.prevent="logout">
          <div class="flex justify-between py-4 border-b border-foreground/10">
            <InputLabel> Logout </InputLabel>
            <Button variant="secondary" :onclick="onLogout">Logout</Button>
          </div>
        </form>
        <div class="flex justify-between py-4 border-b border-foreground/10">
          <InputLabel> Theme </InputLabel>
          <ThemeSwitch />
        </div>

        <UpdateProfileInformationForm
          v-if="$page.props.jetstream.canUpdateProfileInformation"
          class="py-4 border-b border-foreground/10"
          :user="$page.props.auth.user"
        />

        <UpdatePasswordForm
          v-if="$page.props.jetstream.canUpdatePassword"
          class="mt-10 sm:mt-0"
        />

        <LegalForm :user="$page.props.auth.user" />

        <LogoutOtherBrowserSessionsForm
          :sessions="sessions"
          class="mt-10 sm:mt-0"
        />

        <template v-if="$page.props.jetstream.hasAccountDeletionFeatures">
          <DeleteUserForm class="mt-10 sm:mt-0" />
        </template>

        <!-- LOGOUT Button -->
      </BaseContainer>
    </template>
  </AuthenticatedLayout>
</template>
