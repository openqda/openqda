<script setup lang="ts">
import CreateTeamForm from './Partials/CreateTeamForm.vue';
import DeleteTeamForm from './Partials/DeleteTeamForm.vue';
import TeamMemberManager from './Partials/TeamMemberManager.vue';
import UpdateTeamNameForm from './Partials/UpdateTeamNameForm.vue';

const props = defineProps({
  hasTeam: Boolean,
  team: Object,
  permissions: Object,
  availableRoles: Array,
  teamOwner: Boolean,
  project: Object,
});
</script>

<template>
  <CreateTeamForm :projectId="project.id" v-if="!hasTeam" />
  <UpdateTeamNameForm
    v-if="hasTeam"
    :team="team"
    :permissions="props.permissions"
  />
  <TeamMemberManager
    v-if="hasTeam"
    :team="team"
    :available-roles="availableRoles"
    :user-permissions="props.permissions"
    :team-owner="teamOwner"
    :project="project"
  />
  <DeleteTeamForm
    v-if="permissions?.canDeleteTeam && !team.personal_team"
    class="mt-10 sm:mt-0"
    :team="team"
  />
</template>

<style scoped></style>
