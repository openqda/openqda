<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ActionMessage from '../../../Components/ActionMessage.vue';
import FormSection from '../../../Components/FormSection.vue';
import InputError from '../../../form/InputError.vue';
import InputLabel from '../../../form/InputLabel.vue';
import InputField from '../../../form/InputField.vue';
import Button from '../../../Components/interactive/Button.vue';
import ProfileImage from '../../../Components/user/ProfileImage.vue';

const props = defineProps({
  user: Object,
});

const form = useForm({
  _method: 'PUT',
  name: props.user.name,
  email: props.user.email,
  photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);

const updateProfileInformation = () => {
  if (photoInput.value) {
    form.photo = photoInput.value.files[0];
  }

  form.post(route('user-profile-information.update'), {
    errorBag: 'updateProfileInformation',
    preserveScroll: true,
    onSuccess: () => clearPhotoFileInput(),
  });
};

const sendEmailVerification = () => {
  verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
  photoInput.value.click();
};

const updatePhotoPreview = () => {
  const photo = photoInput.value.files[0];

  if (!photo) {
    return;
  }

  const reader = new FileReader();

  reader.onload = (e) => {
    photoPreview.value = e.target.result;
  };

  reader.readAsDataURL(photo);
};

const deletePhoto = () => {
  router.delete(route('current-user-photo.destroy'), {
    preserveScroll: true,
    onSuccess: () => {
      photoPreview.value = null;
      clearPhotoFileInput();
    },
  });
};

const clearPhotoFileInput = () => {
  if (photoInput.value?.value) {
    photoInput.value.value = null;
  }
};
</script>

<template>
  <FormSection @submitted="updateProfileInformation">
    <template #title> Profile Information</template>
    <template #form>
      <!-- Name -->
      <div class="">
        <InputLabel for="name" value="Name" />
        <InputField
          id="name"
          v-model="form.name"
          type="text"
          class="mt-1 block w-full"
          autocomplete="name"
        />
        <InputError :message="form.errors.name" class="mt-2" />
      </div>

      <!-- Email -->
      <div class="">
        <InputLabel for="email" value="Email" class="mt-4" />
        <InputField
          id="email"
          v-model="form.email"
          type="email"
          class="mt-1 block w-full"
          autocomplete="username"
        />
        <InputError :message="form.errors.email" class="mt-2" />

        <div
          v-if="
            $page.props.jetstream.hasEmailVerification &&
            user.email_verified_at === null
          "
        >
          <p class="text-sm mt-2">
            Your email address is unverified.

            <Link
              :href="route('verification.send')"
              method="post"
              as="button"
              class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-cerulean-700"
              @click.prevent="sendEmailVerification"
            >
              Click here to re-send the verification email.
            </Link>
          </p>

          <div
            v-show="verificationLinkSent"
            class="mt-2 font-medium text-sm text-confirmative"
          >
            A new verification link has been sent to your email address.
          </div>
        </div>
      </div>

      <!-- Profile Photo -->
      <div v-if="$page.props.jetstream.managesProfilePhotos">
        <!-- Profile Photo File Input -->
        <input
          ref="photoInput"
          type="file"
          class="hidden"
          @change="updatePhotoPreview"
        />

        <InputLabel for="photo" value="Photo" class="mt-4" />

        <!-- Current Profile Photo -->
        <ProfileImage
          :email="user.email"
          :src="user.profile_photo_url"
          :name="user.name"
          class="h-20 w-20"
        />

        <!-- New Profile Photo Preview -->
        <div v-show="photoPreview" class="mt-2">
          <span
            class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
            :style="'background-image: url(\'' + photoPreview + '\');'"
          />
        </div>

        <Button
          class="mt-2 mr-2"
          variant="outline"
          @click.prevent="selectNewPhoto"
        >
          Select A New Photo
        </Button>

        <Button
          v-if="user.profile_photo_path"
          type="button"
          variant="secondary"
          class="mt-2"
          @click.prevent="deletePhoto"
        >
          Remove Photo
        </Button>

        <InputError :message="form.errors.photo" class="mt-2" />
      </div>
    </template>

    <template #actions>
      <ActionMessage :on="form.recentlySuccessful" class="mr-3">
        Saved.
      </ActionMessage>

      <Button
        type="submit"
        :class="{ 'opacity-25': form.processing }"
        :disabled="form.processing"
      >
        <span v-if="form.processing">Saving</span>
        <span v-else>Save</span>
      </Button>
    </template>
  </FormSection>
</template>
