<script setup>
import { ref } from 'vue';
import Dropdown from './Dropdown.vue';
import DropdownLink from './DropdownLink.vue';
import { router, useForm } from '@inertiajs/vue3';
import { Project } from '../state/Project.js';
import ProfileImage from './user/ProfileImage.vue';
import Button from './interactive/Button.vue';
import TextInput from '../form/InputField.vue';
import TextArea from '../form/TextArea.vue';
import Checkbox from './Checkbox.vue';
import { flashMessage } from './notification/flashMessage.js';

function onLogout() {
  router.post(route('logout'));
}

function getprofileLink(profileUrl) {
  const projectId = Project.getId();
  return Project.isValidId(projectId)
    ? `${profileUrl}?projectId=${projectId}`
    : profileUrl;
}

//-------------------------------------------------
// Feedback form
//-------------------------------------------------
const feedbackFormIsActive = ref(false);
const feedbackForm = useForm({
  title: '',
  problem: '',
  contact: false,
});

function enableFeedback() {
  if (!feedbackFormIsActive.value) {
    feedbackFormIsActive.value = true;
  }
}

function disableFeedback() {
  if (feedbackFormIsActive.value) {
    feedbackFormIsActive.value = false;
  }
}

async function submitFeedback(e) {
  e.preventDefault();
  e.stopPropagation();
  e.stopImmediatePropagation();
  const projectId = Project.getId();
  const formData = feedbackForm.data();
  const location = window.location.href;
  const payload = { projectId, location, ...formData };
  try {
    const response = await axios.post('/user/feedback', payload);
    if (response.data.sent) {
      flashMessage('Your feedback has been submitted');
      feedbackFormIsActive.value = false;
    }
  } catch (error) {
    console.error('Error during feedback submission:', error);
    flashMessage(
      error.response.data.message ||
        'Error while sending feedback, likely too many feedbacks in a short time.'
    );
  }
}
</script>

<template>
  <Dropdown align="right" width="48" :prevent="feedbackFormIsActive">
    <template #trigger>
      <button
        class="flex text-sm relative transition w-16 h-16"
        v-if="$page.props.jetstream.managesProfilePhotos"
      >
        <ProfileImage
          :alt="$page.props.auth.user.name"
          :src="$page.props.auth.user.profile_photo_url"
        />
      </button>

      <span v-else class="inline-flex">
        <button
          class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out bg-white border border-transparent rounded-md hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50"
          type="button"
        >
          {{ $page.props.auth.user.name }}
          <svg
            class="ml-2 -mr-0.5 h-4 w-4"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M19.5 8.25l-7.5 7.5-7.5-7.5"
            />
          </svg>
        </button>
      </span>
    </template>

    <template #content>
      <!-- Account Management -->
      <div class="block px-4 py-2 text-xs text-gray-400">
        <span v-if="feedbackFormIsActive">Provide feedback</span>
        <span v-else>Manage Account</span>
      </div>

      <DropdownLink
        :href="getprofileLink(route('profile.show'))"
        v-show="!feedbackFormIsActive"
      >
        Profile
      </DropdownLink>

      <DropdownLink
        v-if="$page.props.jetstream.hasApiFeatures"
        :href="route('api-tokens.index')"
      >
        API Tokens
      </DropdownLink>

      <form
        @submit.prevent="submitFeedback"
        v-if="feedbackFormIsActive"
        class="m-2"
      >
        <TextInput
          id="feedback-title"
          v-model="feedbackForm.title"
          type="text"
          class="mt-1 block w-full"
          placeholder="Title or summary"
          required
          autofocus
        />
        <TextArea
          id="feedback-problem"
          v-model="feedbackForm.problem"
          class="mt-1 block w-full"
          :rows="5"
          placeholder="Precise description of the problem."
          required
        />

        <label class="mt-1 mb-3 px-1 block w-full">
          <Checkbox
            id="feedback-problem"
            v-model="feedbackForm.contact"
            :checked="feedbackForm.contact"
          />
          <span checked="text-gray-400"> Contact me</span>
        </label>

        <div checked="px-1 block w-full">
          <Button color="cerulean" label="Send" type="submit" />
          <Button
            color="silver"
            label="Cancel"
            class="float-end"
            @click="disableFeedback"
          />
        </div>
      </form>
      <DropdownLink as="a" href="#" @click="enableFeedback" v-else>
        Provide feedback
      </DropdownLink>

      <div class="border-t border-gray-200" v-show="!feedbackFormIsActive" />

      <!-- Authentication -->
      <form @submit.prevent="logout" v-show="!feedbackFormIsActive">
        <DropdownLink @click="onLogout" href="#"> Log Out</DropdownLink>
      </form>
    </template>
  </Dropdown>
</template>
