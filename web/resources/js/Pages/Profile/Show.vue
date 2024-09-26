<script setup>
import DeleteUserForm from '../../Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '../../Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import '../../Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '../../Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '../../Pages/Profile/Partials/UpdateProfileInformationForm.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import InputLabel from '../../ui/form/InputLabel.vue';
import ThemeSwitch from '../../ui/theme/ThemeSwitch.vue';

defineProps({
  confirmsTwoFactorAuthentication: Boolean,
  sessions: Array,
});
</script>

<template>
  <AuthenticatedLayout :menu="false">
    <template #main>
      <div class="w-full lg:w-3/4 xl:w-1/2">
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

        <LogoutOtherBrowserSessionsForm
          :sessions="sessions"
          class="mt-10 sm:mt-0"
        />

        <template v-if="$page.props.jetstream.hasAccountDeletionFeatures">
          <DeleteUserForm class="mt-10 sm:mt-0" />
        </template>
      </div>
    </template>
  </AuthenticatedLayout>
</template>
