a
<script setup>
import { useForm } from '@inertiajs/vue3';
import FormSection from '../../../Components/FormSection.vue';
import InputError from '../../../form/InputError.vue';
import InputLabel from '../../../form/InputLabel.vue';
import PrimaryButton from '../../../Components/PrimaryButton.vue';
import InputField from '../../../form/InputField.vue';

const props = defineProps(['projectId']);
const form = useForm({
  name: '',
  projectId: props.projectId,
});
const createTeam = () => {
  form.post(route('teams.store'), {
    errorBag: 'createTeam',
    preserveScroll: true,
  });
};
</script>

<template>
  <FormSection @submitted="createTeam">
    <template #title> Team Details </template>

    <template #description>
      Create a new team to collaborate with others on projects.
    </template>

    <template #form>
      <div class="col-span-6">
        <InputLabel value="Team Owner" />

        <div class="flex items-center mt-2">
          <div class="leading-tight">
            <div class="text-gray-900">
              {{ $page.props.auth.user.name }}
            </div>
            <div class="text-sm text-gray-500">
              {{ $page.props.auth.user.email }}
            </div>
          </div>
        </div>
      </div>

      <div class="col-span-6 sm:col-span-4">
        <InputLabel for="name" value="Team Name" />
        <InputField
          id="name"
          v-model="form.name"
          type="text"
          class="block w-full mt-1"
          autofocus
        />
        <InputError :message="form.errors.name" class="mt-2" />
      </div>
    </template>

    <template #actions>
      <PrimaryButton
        :class="{ 'opacity-25': form.processing }"
        :disabled="form.processing"
      >
        Create
      </PrimaryButton>
    </template>
  </FormSection>
</template>
