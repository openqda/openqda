<script setup>
import { onMounted, reactive, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import ActionMessage from '../../../Components/ActionMessage.vue';
import ActionSection from '../../../Components/ActionSection.vue';
import ConfirmationModal from '../../../Components/ConfirmationModal.vue';
import DangerButton from '../../../Components/DangerButton.vue';
import DialogModal from '../../../Components/DialogModal.vue';
import FormSection from '../../../Components/FormSection.vue';
import InputError from '../../../form/InputError.vue';
import InputLabel from '../../../form/InputLabel.vue';
import PrimaryButton from '../../../Components/PrimaryButton.vue';
import SecondaryButton from '../../../Components/SecondaryButton.vue';
import SectionBorder from '../../../Components/SectionBorder.vue';
import InputField from '../../../form/InputField.vue';
import ProfileImage from '../../../Components/user/ProfileImage.vue';
import Button from '../../../Components/interactive/Button.vue'

const props = defineProps({
  team: Object,
  availableRoles: Array,
  userPermissions: Object,
  teamOwner: Boolean,
  project: Object,
});

const addTeamMemberForm = useForm({
  email: '',
  role: null,
});

const updateRoleForm = useForm({
  role: null,
});

const makeOwnerForm = useForm({});

const leaveTeamForm = useForm({});
const removeTeamMemberForm = useForm({});

const currentlyManagingRole = ref(false);
const managingRoleFor = ref(null);
const confirmingLeavingTeam = ref(false);
const confirmingMakeOwner = ref(false);
const teamMemberBeingRemoved = ref(null);

const addTeamMember = () => {
  addTeamMemberForm.post(route('team-members.store', props.team), {
    errorBag: 'addTeamMember',
    preserveScroll: true,
    onSuccess: () => addTeamMemberForm.reset(),
  });
};

const cancelTeamInvitation = (invitation) => {
  router.delete(route('team-invitations.destroy', invitation), {
    preserveScroll: true,
  });
};

const manageRole = (teamMember) => {
  managingRoleFor.value = teamMember;
  updateRoleForm.role = teamMember.membership.role;
  currentlyManagingRole.value = true;
};

const updateRole = () => {
  updateRoleForm.put(
    route('team-members.update', [props.team, managingRoleFor.value]),
    {
      preserveScroll: true,
      onSuccess: () => (currentlyManagingRole.value = false),
    }
  );
};

const confirmLeavingTeam = () => {
  confirmingLeavingTeam.value = true;
};

const leaveTeam = () => {
  leaveTeamForm.delete(
    route('team-members.destroy', [props.team, usePage().props.auth.user])
  );
};

/**
 * Make the current user the owner of the team and project.
 */
const makeOwner = () => {
  const payload = reactive({
    teamId: props.team.id,
    userId: makeOwnerForm.user.id,
    projectId: props.project.id,
  });
  router.post(route('team-members.make-owner'), payload, {
    preserveScroll: true,
    reload: true,
    onSuccess: () => {
      confirmingMakeOwner.value = false;
    },
  });
};

const confirmTeamMemberRemoval = (teamMember) => {
  teamMemberBeingRemoved.value = teamMember;
};

const removeTeamMember = () => {
  removeTeamMemberForm.delete(
    route('team-members.destroy', [props.team, teamMemberBeingRemoved.value]),
    {
      errorBag: 'removeTeamMember',
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => (teamMemberBeingRemoved.value = null),
    }
  );
};

const displayableRole = (role) => {
  return props.availableRoles.find((r) => r.key === role).name;
};

onMounted(() => {
  if (props.availableRoles.length === 1) {
    addTeamMemberForm.role = props.availableRoles[0].key;
  }
});
</script>

<template>
  <div class="space-y-6">
    <div>
      <!-- Team Owner Information -->
      <InputLabel value="Team Owner" />

      <div class="flex items-center mt-2">
        <ProfileImage
          class="w-12 h-12 rounded-full object-cover"
          :src="team.owner.profile_photo_url"
          :email="team.owner.email"
          :alt="team.owner.name"
        />

        <div class="ml-4 leading-tight">
          <div class="text-foreground/60">{{ team.owner.name }}</div>
          <div class="text-foreground/60">
            {{ team.owner.email }}
          </div>
        </div>
      </div>
    </div>

    <div v-if="team.users.length > 0">
      <!-- Manage Team Members -->

      <ActionSection>
        <!-- Team Member List -->
        <template #content>
            <div class="flex justify-between align-baseline my-6">
                <InputLabel value="Team Members" class="mb-3" />
                <Button
                    variant="outline-confirmative">
                    Add
                </Button>
            </div>
          <div class="space-y-6">
            <div
              v-for="user in team.users"
              :key="user.id"
              class="flex items-center justify-between"
            >
              <div class="flex items-center">
                <img
                  class="w-8 h-8 rounded-full object-cover"
                  :src="user.profile_photo_url"
                  :alt="user.name"
                />
                <div class="ml-4">
                  {{ user.name }}
                </div>
              </div>

              <div class="flex items-center space-x-2">
                <!-- Manage Team Member Role -->

                <Button
                  v-if="teamOwner"
                  variant="outline"
                  title="Make Owner of Team and Project"
                  @click="
                    confirmingMakeOwner = true;
                    makeOwnerForm.user = user;
                  "
                >
                  Make owner
                </Button>
                <!-- Manage Team Member Role -->
                <Button
                  v-if="userPermissions.canAddTeamMembers && availableRoles.length"
                  variant="outline"
                  @click="manageRole(user)"
                >
                  {{ displayableRole(user.membership.role) }}
                </Button>

                <div
                  v-else-if="availableRoles.length"
                  class="ml-2 text-sm text-gray-400"
                >
                  {{ displayableRole(user.membership.role) }}
                </div>

                <!-- Leave Team -->
                <Button
                  v-if="$page.props.auth.user.id === user.id"
                  variant="destructive"
                  @click="confirmLeavingTeam"
                >
                  Leave
                </Button>

                <!-- Remove Team Member -->
                <Button
                  v-else-if="userPermissions.canRemoveTeamMembers"
                  variant="destructive"
                  @click="confirmTeamMemberRemoval(user)"
                >
                  Remove
                </Button>
              </div>
            </div>
          </div>
        </template>
      </ActionSection>
    </div>

    <div v-if="team.team_invitations.length > 0 && userPermissions.canAddTeamMembers">
      <!-- Team Member Invitations -->
      <ActionSection class="mt-10 sm:mt-0">
        <template #title> Pending Team Invitations</template>

        <template #description>
          These people have been invited to your team and have been sent an
          invitation email. They may join the team by accepting the email
          invitation.
        </template>

        <!-- Pending Team Member Invitation List -->
        <template #content>
          <div class="space-y-6">
            <div
              v-for="invitation in team.team_invitations"
              :key="invitation.id"
              class="flex items-center justify-between"
            >
              <div class="text-gray-600">
                {{ invitation.email }}
              </div>

              <div class="flex items-center">
                <!-- Cancel Team Invitation -->
                <button
                  v-if="userPermissions.canRemoveTeamMembers"
                  class="cursor-pointer ml-6 text-sm text-red-700 focus:outline-none"
                  @click="cancelTeamInvitation(invitation)"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </template>
      </ActionSection>
    </div>

    <!-- Role Management Modal -->
    <DialogModal
      :show="currentlyManagingRole"
      @close="currentlyManagingRole = false"
    >
      <template #title> Manage Role</template>

      <template #content>
        <div v-if="managingRoleFor">
          <div
            class="relative z-0 mt-1 border border-gray-200 rounded-lg cursor-pointer"
          >
            <button
              v-for="(role, i) in availableRoles"
              :key="role.key"
              type="button"
              class="relative px-4 py-3 inline-flex w-full rounded-lg focus:z-10 focus:outline-none focus:border-cerulean-700 focus:ring-2 focus:ring-cerulean-700"
              :class="{
                'border-t border-gray-200 focus:border-none rounded-t-none':
                  i > 0,
                'rounded-b-none': i !== Object.keys(availableRoles).length - 1,
              }"
              @click="updateRoleForm.role = role.key"
            >
              <div
                :class="{
                  'opacity-50':
                    updateRoleForm.role && updateRoleForm.role !== role.key,
                }"
              >
                <!-- Role Name -->
                <div class="flex items-center">
                  <div
                    class="text-sm text-gray-600"
                    :class="{
                      'font-semibold': updateRoleForm.role === role.key,
                    }"
                  >
                    {{ role.name }}
                  </div>

                  <svg
                    v-if="updateRoleForm.role == role.key"
                    class="ml-2 h-5 w-5 text-green-400"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                </div>

                <!-- Role Description -->
                <div class="mt-2 text-xs text-gray-600">
                  {{ role.description }}
                </div>
              </div>
            </button>
          </div>
        </div>
      </template>

      <template #footer>
        <SecondaryButton @click="currentlyManagingRole = false">
          Cancel
        </SecondaryButton>

        <PrimaryButton
          class="ml-3"
          :class="{ 'opacity-25': updateRoleForm.processing }"
          :disabled="updateRoleForm.processing"
          @click="updateRole"
        >
          Save
        </PrimaryButton>
      </template>
    </DialogModal>

    <div v-if="userPermissions.canAddTeamMembers">
      <!-- Add Team Member -->
      <FormSection @submitted="addTeamMember">
        <template #title> Add Team Member</template>

        <template #description>
          Add a new team member to your team, allowing them to collaborate with
          you.
        </template>

        <template #form>
          <div class="col-span-6">
            <div class="max-w-xl text-sm text-gray-600">
              Please provide the email address of the person you would like to
              add to this team.
            </div>
          </div>

          <!-- Member Email -->
          <div class="col-span-6 sm:col-span-4">
            <InputLabel for="email" value="Email" />
            <InputField
              id="email"
              v-model="addTeamMemberForm.email"
              type="email"
              class="mt-1 block w-full"
            />
            <InputError
              :message="addTeamMemberForm.errors.email"
              class="mt-2"
            />
          </div>

          <!-- Role -->
          <div
            v-if="availableRoles.length > 0"
            class="col-span-6 lg:col-span-4"
          >
            <InputLabel for="roles" value="Role" />
            <InputError :message="addTeamMemberForm.errors.role" class="mt-2" />

            <div
              class="relative z-0 mt-1 border border-gray-200 rounded-lg cursor-pointer"
            >
              <button
                v-for="(role, i) in availableRoles"
                :key="role.key"
                type="button"
                class="relative px-4 py-3 inline-flex w-full rounded-lg focus:z-10 focus:outline-none focus:border-cerulean-700 focus:ring-2 focus:ring-cerulean-700"
                :class="{
                  'border-t border-gray-200 focus:border-none rounded-t-none':
                    i > 0,
                  'rounded-b-none': i != Object.keys(availableRoles).length - 1,
                }"
                @click="addTeamMemberForm.role = role.key"
              >
                <div
                  :class="{
                    'opacity-50':
                      addTeamMemberForm.role &&
                      addTeamMemberForm.role != role.key,
                  }"
                >
                  <!-- Role Name -->
                  <div class="flex items-center">
                    <div
                      class="text-sm text-gray-600"
                      :class="{
                        'font-semibold': addTeamMemberForm.role == role.key,
                      }"
                    >
                      {{ role.name }}
                    </div>

                    <svg
                      v-if="addTeamMemberForm.role == role.key"
                      class="ml-2 h-5 w-5 text-green-400"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke-width="1.5"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                    </svg>
                  </div>

                  <!-- Role Description -->
                  <div class="mt-2 text-xs text-gray-600 text-left">
                    {{ role.description }}
                  </div>
                </div>
              </button>
            </div>
          </div>
        </template>

        <template #actions>
          <ActionMessage
            :on="addTeamMemberForm.recentlySuccessful"
            class="mr-3"
          >
            Added.
          </ActionMessage>

          <Button
              v-if="availableRoles?.length > 1"
            :class="{ 'opacity-25': addTeamMemberForm.processing }"
            :disabled="addTeamMemberForm.processing"
          >
            Add
          </Button>
        </template>
      </FormSection>
    </div>

    <!-- Make Owner Confirmation Modal -->
    <ConfirmationModal
      :show="confirmingMakeOwner"
      @close="confirmingMakeOwner = false"
    >
      <template #title> Change owner of project and team</template>

      <template #content>
        You are about to make this person the owner of the project and team. Are
        you sure you want to do this? This is reversible only by the new owner.
      </template>

      <template #footer>
        <SecondaryButton @click="confirmingMakeOwner = false">
          Cancel
        </SecondaryButton>

        <PrimaryButton
          class="ml-3"
          :class="{ 'opacity-25': makeOwnerForm.processing }"
          :disabled="makeOwnerForm.processing"
          @click="makeOwner"
        >
          Confirm
        </PrimaryButton>
      </template>
    </ConfirmationModal>
    <!-- Leave Team Confirmation Modal -->
    <ConfirmationModal
      :show="confirmingLeavingTeam"
      @close="confirmingLeavingTeam = false"
    >
      <template #title> Leave Team</template>

      <template #content>
        Are you sure you would like to leave this team?
      </template>

      <template #footer>
        <SecondaryButton @click="confirmingLeavingTeam = false">
          Cancel
        </SecondaryButton>

        <DangerButton
          class="ml-3"
          :class="{ 'opacity-25': leaveTeamForm.processing }"
          :disabled="leaveTeamForm.processing"
          @click="leaveTeam"
        >
          Leave
        </DangerButton>
      </template>
    </ConfirmationModal>

    <!-- Remove Team Member Confirmation Modal -->
    <ConfirmationModal
      :show="teamMemberBeingRemoved"
      @close="teamMemberBeingRemoved = null"
    >
      <template #title> Remove Team Member</template>

      <template #content>
        Are you sure you would like to remove this person from the team?
      </template>

      <template #footer>
        <SecondaryButton @click="teamMemberBeingRemoved = null">
          Cancel
        </SecondaryButton>

        <DangerButton
          class="ml-3"
          :class="{ 'opacity-25': removeTeamMemberForm.processing }"
          :disabled="removeTeamMemberForm.processing"
          @click="removeTeamMember"
        >
          Remove
        </DangerButton>
      </template>
    </ConfirmationModal>
  </div>
</template>
