<script setup lang="ts">
import CreateTeamForm from './Partials/CreateTeamForm.vue';
import DeleteTeamForm from './Partials/DeleteTeamForm.vue';
import Headline2 from '../../Components/layout/Headline2.vue';
import SectionBorder from '../../Components/SectionBorder.vue';
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
  <div v-if="hasTeam">
    <Headline2>My Project Team</Headline2>

    <div>
      <div class="py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <UpdateTeamNameForm :team="team" :permissions="props.permissions" />

        <TeamMemberManager
          class="mt-10 sm:mt-0"
          :team="team"
          :available-roles="availableRoles"
          :user-permissions="props.permissions"
          :team-owner="teamOwner"
          :project="project"
        />

        <template v-if="permissions.canDeleteTeam && !team.personal_team">
          <SectionBorder />

          <DeleteTeamForm class="mt-10 sm:mt-0" :team="team" />
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
