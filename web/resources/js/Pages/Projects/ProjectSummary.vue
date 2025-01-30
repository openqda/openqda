<script setup lang="ts">
import { ExclamationTriangleIcon, PencilIcon } from '@heroicons/vue/20/solid';
import { inject, ref } from 'vue';
import { request } from '../../utils/http/BackendRequest';
import RenameDialog from '../../dialogs/RenameDialog.vue';
import InputLabel from '../../form/InputLabel.vue';
import Button from '../../Components/interactive/Button.vue';
import { cn } from '../../utils/css/cn';
import DeleteDialog from '../../dialogs/DeleteDialog.vue';
import { router } from '@inertiajs/vue3';

const project = inject('project');
const renameTarget = ref(null);
const deleteTarget = ref(null);

const submitRename = async ({ name }) => {
  const { type } = renameTarget.value;
  const payload = { value: name, type };

  const response = await request({
    url: `/projects/update/${project.id}`,
    type: 'post',
    body: payload,
  });

  if (!response.error) {
    project[type] = name;
  }

  return response;
};

const deleteProject = async () => {
  const path = route('project.destroy', { project: project.id });
  router.delete(path);
  return true;
};
</script>

<template>
  <div class="divide-y divide-foreground/20">
    <!-- RENAME TITLE -->
    <div class="py-6">
      <InputLabel>Project Name</InputLabel>

      <div class="flex justify-between items-center">
        <span class="flex-grow text-foreground/80 tracking-wide pe-4">{{
          project.name
        }}</span>
        <Button
          variant="outline-secondary"
          @click="
            renameTarget = {
              id: project.id,
              name: project.name,
              type: 'name',
            }
          "
        >
          <PencilIcon class="w-4 h-4 me-1" />
          <span>Edit</span>
        </Button>
      </div>
    </div>

    <!-- RENAME DESCRIPTION -->
    <div class="py-6">
      <InputLabel>Project Description</InputLabel>

      <div class="flex justify-between">
        <span
          :class="
            cn(
              'flex-grow tracking-wide pe-4',
              project.description ? 'text-foreground/80' : 'text-foreground/40'
            )
          "
          >{{
            project.description ?? 'Add a project description (optional)'
          }}</span
        >
        <Button
          variant="outline-secondary"
          @click="
            renameTarget = {
              id: project.id,
              name: project.description,
              type: 'description',
            }
          "
        >
          <PencilIcon class="w-4 h-4 me-1" />
          <span>Edit</span>
        </Button>
      </div>
    </div>

    <!-- DELETE PROJECT -->
    <div class="py-6">
      <InputLabel>Delete Project</InputLabel>

      <div class="flex justify-between items-center">
        <div class="flex items-center tracking-wide text-foreground/80">
          <ExclamationTriangleIcon class="h-5 w-5" />
          <span class="ms-2">This action cannot be undone</span>
        </div>
        <Button variant="destructive" @click="deleteTarget = project">
          <span>Delete</span>
        </Button>
      </div>
    </div>
  </div>
  <RenameDialog
    :title="`Edit project ${renameTarget?.type}`"
    :target="renameTarget"
    :submit="submitRename"
    :emptyAllowed="renameTarget?.type === 'description'"
    @renamed="renameTarget = null"
    @cancelled="renameTarget = null"
  />
  <DeleteDialog
    :target="deleteTarget"
    :submit="deleteProject"
    @cancelled="deleteTarget = null"
    challenge="name"
  >
  </DeleteDialog>
</template>

<style scoped></style>
