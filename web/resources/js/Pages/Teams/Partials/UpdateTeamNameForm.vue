<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '../../../Components/ActionMessage.vue';
import FormSection from '../../../Components/FormSection.vue';
import InputError from '../../../form/InputError.vue';
import InputLabel from '../../../form/InputLabel.vue';
import PrimaryButton from '../../../Components/PrimaryButton.vue';
import InputField from '../../../form/InputField.vue';
import Button from '../../../Components/interactive/Button.vue'
import ProfileImage from '../../../Components/user/ProfileImage.vue'

const props = defineProps({
  team: Object,
  permissions: Object,
});

const form = useForm({
  name: props.team.name,
});

const updateTeamName = () => {
  form.put(route('teams.update', props.team), {
    errorBag: 'updateTeamName',
    preserveScroll: true,
  });
};
</script>

<template>
  <FormSection @submitted="updateTeamName">
    <template #form>
        <!-- Team Name -->
        <div class="mt-4">
            <InputLabel for="name" value="Team Name" />

            <InputField
                id="name"
                v-model="form.name"
                type="text"
                class="mt-1 block w-full"
                :disabled="!permissions.canUpdateTeam"
            />

            <InputError :message="form.errors.name" class="mt-2" />
        </div>

    </template>



    <template v-if="permissions.canUpdateTeam" #actions>
      <ActionMessage :on="form.recentlySuccessful" class="mr-3">
        Saved.
      </ActionMessage>

      <Button
          type="submit"
          variant="secondary"
        :class="{ 'opacity-25': form.processing }"
        :disabled="form.processing"
      >
        Save
      </Button>
    </template>
  </FormSection>
</template>
