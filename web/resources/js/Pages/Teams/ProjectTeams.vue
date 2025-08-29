<script setup lang="ts">
import CreateTeamForm from './Partials/CreateTeamForm.vue';
import DeleteTeamForm from './Partials/DeleteTeamForm.vue';
import TeamMemberManager from './Partials/TeamMemberManager.vue';
import UpdateTeamNameForm from './Partials/UpdateTeamNameForm.vue';
import { useTeam } from '../../domain/teams/useTeam';
import { onMounted } from 'vue';
import ActivityIndicator from '../../Components/ActivityIndicator.vue';

const { teamConfig, loadTeamConfig } = useTeam();
const props = defineProps({
  project: {
    type: Object,
    required: true,
  },
});

onMounted(async () => {
  const projectId = props.project.id;
  if (!teamConfig.value?.projectId !== projectId) {
    await loadTeamConfig({ projectId });
  }
});
</script>

<template>
  <CreateTeamForm
    :projectId="project.id"
    v-if="teamConfig.loaded && !teamConfig.hasTeam"
  />
  <UpdateTeamNameForm
    v-if="teamConfig.loaded && teamConfig.hasTeam"
    :team="teamConfig.team"
    :permissions="teamConfig.permissions"
  />
  <TeamMemberManager
    v-if="teamConfig.loaded && teamConfig.hasTeam"
    :team="teamConfig.team"
    :available-roles="teamConfig.availableRoles"
    :user-permissions="teamConfig.permissions"
    :team-owner="teamConfig.teamOwner"
    :project="project"
  />
  <DeleteTeamForm
    v-if="
      teamConfig.loaded &&
      teamConfig.permissions?.canDeleteTeam &&
      !teamConfig.team.personal_team
    "
    class="mt-10 sm:mt-0"
    :team="teamConfig.team"
  />
  <ActivityIndicator v-if="!teamConfig.loaded"
    >Loading Project Team Settings</ActivityIndicator
  >
</template>

<style scoped></style>
