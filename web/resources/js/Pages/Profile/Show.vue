<script setup>
import DeleteUserForm from '../../Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '../../Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import UpdatePasswordForm from '../../Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '../../Pages/Profile/Partials/UpdateProfileInformationForm.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import InputLabel from '../../form/InputLabel.vue';
import ThemeSwitch from '../../theme/ThemeSwitch.vue';
import Button from '../../Components/interactive/Button.vue';
import BaseContainer from '../../Layouts/BaseContainer.vue';
import LegalForm from './Partials/LegalForm.vue';
import { useUsers } from '../../domain/teams/useUsers.js';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline/index.js';
import { Preferences } from '../../domain/user/Preferences.js';
import { attemptAsync } from '../../Components/notification/attemptAsync.js';
// import '../../Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';

const { userIsVerified } = useUsers();

defineProps({
  confirmsTwoFactorAuthentication: Boolean,
  sessions: Array,
});

const onThemeChange = async (theme) => {
  await attemptAsync(() => Preferences.updateTheme({ theme }));
};
</script>

<template>
  <AuthenticatedLayout :menu="false">
    <template #main>
      <BaseContainer class="w-full lg:w-3/4 xl:w-1/2 p-1 lg:p-3">
        <div
          v-if="!userIsVerified()"
          class="p-4 rounded border border-destructive text-sm text-foreground"
        >
          <div class="flex items-center gap-2 mb-2">
            <ExclamationTriangleIcon
              class="w-10 h-10 text-destructive"
              aria-hidden="true"
            />
            <p>
              Your email address has not yet been verified. You will not be able
              to access all OpenQDA features until this has been done.
            </p>
          </div>
        </div>
        <form @submit.prevent="logout">
          <div class="flex justify-between py-4 border-b border-foreground/10">
            <InputLabel> Logout </InputLabel>
            <Button variant="secondary" :onclick="onLogout">Logout</Button>
          </div>
        </form>
        <div class="flex justify-between py-4 border-b border-foreground/10">
          <InputLabel> Theme </InputLabel>
          <ThemeSwitch @change="onThemeChange" />
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
