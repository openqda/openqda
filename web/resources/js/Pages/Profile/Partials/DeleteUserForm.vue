<script setup>
import { onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '../../../Components/ActionSection.vue';
import InputError from '../../../form/InputError.vue';
import InputField from '../../../form/InputField.vue';
import { request } from '../../../utils/http/BackendRequest.js';
import { useUsers } from '../../../domain/teams/useUsers.js';
import Button from '../../../Components/interactive/Button.vue';
import DialogBase from '../../../dialogs/DialogBase.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);
const ownTeams = ref([]);
const loading = ref(true);
const { getOwnUser } = useUsers();

onMounted(async () => {
  const user = getOwnUser();
  const { response, error } = await request({
    url: `/user/${user.id}/owned-teams`,
    type: 'get',
  });

  if (!error) {
    ownTeams.value = response.data.ownTeams;
  }
  loading.value = false;
});

const form = useForm({
  password: '',
});

const confirmUserDeletion = () => {
  confirmingUserDeletion.value = true;

  setTimeout(() => passwordInput.value.focus(), 250);
};

const deleteUser = () => {
  form.delete(route('current-user.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
    onError: () => passwordInput.value.focus(),
    onFinish: () => form.reset(),
  });
};

const closeModal = () => {
  confirmingUserDeletion.value = false;

  form.reset();
};
</script>

<template>
  <ActionSection>
    <template #title> Delete Account </template>

    <template #description> Permanently delete your account. </template>

    <template #content>
      <div class="max-w-xl text-sm text-gray-600">
        Once your account is deleted, all of its resources and data will be
        permanently deleted. Before deleting your account, please download any
        data or information that you wish to retain.
        <div
          class="text-red-900 font-bold bg-red-300 p-2 border-l-red-600 border-l-8"
          v-if="ownTeams.length > 0"
        >
          If you are the owner of any teams, you can either make someone else
          the owner or delete the team.
        </div>
      </div>

      <!-- List of projects -->
      <div v-if="ownTeams.length > 0" class="mt-5">
        <div class="font-bold text-gray-700 mb-2">
          Projects with your teams:
        </div>
        <ul>
          <li v-for="team in ownTeams" :key="team.id" class="mb-2">
            <a
              target="_blank"
              v-if="team.projects.length > 0"
              :href="'/projects/' + team.projects[0].id + '/overview#collab'"
              class="text-secondary hover:underline"
            >
              {{ team.projects[0].name }} -
              {{ team.projects[0].description }}
            </a>
            <span v-else class="text-foreground/60"
              >Please contact us to delete your account</span
            >
          </li>
        </ul>
      </div>

      <div class="mt-5">
        <Button
          variant="destructive"
          @click="confirmUserDeletion"
          :disabled="ownTeams.length > 0 || loading"
        >
          Delete Account
        </Button>
      </div>

      <!-- Delete Account Confirmation Modal -->
      <DialogBase
        :show="confirmingUserDeletion"
        @close="closeModal"
        :static="false"
        :destructive="true"
      >
        <template #title> Delete Account </template>

        <template #body>
          Are you sure you want to delete your account? Once your account is
          deleted, all of its resources and data will be permanently deleted.
          Please enter your password to confirm you would like to permanently
          delete your account.

          <div class="mt-4">
            <InputField
              ref="passwordInput"
              v-model="form.password"
              type="password"
              class="mt-1 block w-3/4"
              placeholder="Password"
              autocomplete="current-password"
              @keyup.enter="deleteUser"
            />

            <InputError :message="form.errors.password" class="mt-2" />
          </div>
        </template>

        <template #footer>
          <div class="flex w-full justify-between items-center">
            <Button variant="outline" @click="closeModal"> Cancel </Button>
            <Button
              variant="destructive"
              :class="{ 'opacity-25': form.processing }"
              :disabled="form.processing"
              @click="deleteUser"
            >
              Delete Account
            </Button>
          </div>
        </template>
      </DialogBase>
    </template>
  </ActionSection>
</template>
