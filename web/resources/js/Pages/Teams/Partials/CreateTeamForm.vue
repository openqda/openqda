a
<script setup>
import { useForm } from '@inertiajs/vue3';
import FormSection from '../../../Components/FormSection.vue';
import InputError from '../../../form/InputError.vue';
import InputLabel from '../../../form/InputLabel.vue';
import InputField from '../../../form/InputField.vue';
import Button from '../../../Components/interactive/Button.vue'

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
    <template #description>
      Create a new team to collaborate with others on this projects.
    </template>

    <template #form>
      <div class="space-y-6">
        <div>
          <InputLabel for="name" value="Team Name" />
          <InputField
            id="name"
            v-model="form.name"
            type="text"
            class="block w-full mt-1"
            placeholder="Enter a name for the new team"
            autofocus
          />
          <InputError :message="form.errors.name" class="mt-2" />
        </div>

        <div>
          <InputLabel value="Team Owner" />
          <InputField :model-value="$page.props.auth.user.name" readonly />
        </div>
      </div>
    </template>

    <template #actions>
      <Button
        type="submit"
        variant="secondary"
        :class="{ 'opacity-25': form.processing }"
        :disabled="form.processing"
      >
        Create new team
      </Button>
    </template>
  </FormSection>
</template>
