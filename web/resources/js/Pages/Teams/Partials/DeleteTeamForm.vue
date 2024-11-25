<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import ActionSection from '../../../Components/ActionSection.vue';
import DangerButton from '../../../Components/DangerButton.vue';
import DeleteDialog from '../../../dialogs/DeleteDialog.vue';
import { asyncTimeout } from '../../../utils/asyncTimeout.js';

const props = defineProps({
  team: Object,
});

const deleteTarget = ref(null);
const form = useForm({});
// TOOD move to composable useTeams and avoid
// reloading the page
const deleteTeam = async () => {
  form.delete(route('teams.destroy', props.team));
  await asyncTimeout(300);
  router.reload();
  await asyncTimeout(300);
  return true;
};
</script>

<template>
  <ActionSection>
    <template #title> Delete Team </template>
    <template #description> Permanently delete this team. </template>
    <template #content>
      <div class="flex justify-between items-center">
        <div class="text-sm text-foreground/60">
          Once a team is deleted, all of its resources and data will be
          available only to the team owner.
        </div>
        <div class="">
          <DangerButton @click="deleteTarget = props.team">
            Delete Team
          </DangerButton>
        </div>
      </div>
      <!-- Delete Team Confirmation Modal -->
      <DeleteDialog
        :title="`Delete Team '${team.name}'`"
        message="Once a team is deleted, all of its resources and data will be available only to the team owner."
        challenge="name"
        :target="deleteTarget"
        :submit="deleteTeam"
        @deleted="deleteTarget = null"
        @cancelled="deleteTarget = null"
      />
    </template>
  </ActionSection>
</template>
