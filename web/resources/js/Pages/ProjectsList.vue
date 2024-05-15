<template>
  <div>
    <Head title="Projects"></Head>
    <FlashMessage v-if="$page.props.flash.message" :flash="$page.props.flash" />
    <div
      class="min-h-screen bg-center bg-no-repeat bg-cover"
      :style="{
        backgroundImage: `url('${$page.props.bgtl}') ,url('${$page.props.bgtr}'), url('${$page.props.bgbl}') ,url('${$page.props.bgbr}')`,
        backgroundPosition: 'left top, right top, left bottom, right bottom',
        backgroundSize: '25%',
        backgroundAttachment: 'fixed',
      }"
    >
      <!-- Page Content -->
      <main>
        <div
          class="flex flex-col w-3/4 ml-auto mr-auto items-center h-auto mt-5"
        >
          <img
            :src="$page.props.logo"
            class="w-full sm:w-3/4 md:w-1/2 lg:w-1/4 xl:w-1/5"
          />
        </div>

        <div
          class="flex flex-col w-full md:w-1/2 xl:w-1/3 ml-auto mr-auto items-center mt-5 bg-white p-4 rounded-lg shadow-2xl"
        >
          <div
            class="flex flex-col w-full items-center bg-white p-2 rounded-lg"
            v-if="Object.keys(projectsComputed).length > 0"
          >
            <div class="text-center mt-3 w-full">
              <Headline2>Select Project</Headline2>
            </div>
            <ul class="divide-y divide-silver-100 mt-5 w-full">
              <li
                v-for="project in projectsComputed"
                :key="project.id"
                class="flex justify-between hover:bg-porsche-400 hover:text-white"
              >
                <a
                  :href="route('project.show', project.id)"
                  class="w-full flex justify-between items-center p-2"
                >
                  <span class="font-bold">{{ project.name }}</span>
                  <div class="flex items-center">
                    <!-- Display the "collaborative" pill if the user is the owner of a shared project -->
                    <span
                      v-if="project.isCollaborative && project.isOwner"
                      class="bg-red-700 text-white text-xs rounded-full px-2 py-1 mr-2"
                    >
                      shared
                    </span>
                    <!-- Display the "invited" pill if the user is not the owner -->
                    <span
                      v-if="!project.isOwner"
                      class="bg-cerulean-700 text-white text-xs rounded-full px-2 py-1 mr-2"
                    >
                      Invited
                    </span>
                    <span class="text-silver-500">
                      {{
                        project.description
                          ? project.description.substring(0, 50) +
                            (project.description.length > 50 ? '...' : '')
                          : ''
                      }}
                    </span>
                  </div>
                </a>
              </li>
            </ul>
          </div>

          <div class="flex flex-col w-full mt-3">
            <div class="text-center ml-auto mr-auto mt-3 py-3 w-full">
              <Headline2>Create New Project</Headline2>
            </div>
            <input
              class="border placeholder-silver-300"
              :class="nameError ? 'border-red-400' : 'border-silver-300'"
              type="text"
              v-model="newProject.name"
              placeholder="Give your project a title"
              required
            />
            <textarea
              class="mt-2 border border-silver-200 placeholder-silver-300"
              v-model="newProject.description"
              placeholder="Summarize your project (optional)"
            ></textarea>
            <Button
              color="cerulean"
              :icon="PlusIcon"
              :label="'Create a new project'"
              @click="addProject"
              class="px-1.5 py-2 mt-2"
            />
          </div>

          <!-- History Div -->
          <div
            @click="showHistory = !showHistory"
            class="flex flex-col w-full mt-3"
          >
            <div class="text-center w-full">
              <Headline2
                class="mt-3 flex items-center justify-center cursor-pointer"
              >
                Audit History
                <span v-if="showHistory" class="ml-2">
                  <ChevronUpIcon class="h-5 w-5" />
                </span>
                <span v-else class="ml-2">
                  <ChevronDownIcon class="h-5 w-5" />
                </span>
              </Headline2>
            </div>
          </div>

          <Audit
            v-show="showHistory"
            :audits="audits"
            context="homePage"
          ></Audit>
        </div>
      </main>
    </div>
    <Footer />
  </div>
</template>

<script setup>
/*
 |--------------------------------------------------------------------------
 | Projects List
 |--------------------------------------------------------------------------
 | Page-level component, that shows all projects
 | for the current signed-in user.
 */
import {
  ChevronDownIcon,
  ChevronUpIcon,
  PlusIcon,
} from '@heroicons/vue/20/solid';
import { ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import FlashMessage from '../Components/FlashMessage.vue';
import Button from '../Components/interactive/Button.vue';
import Audit from '../Components/global/Audit.vue';
import Footer from '../Layouts/Footer.vue';
import Headline2 from '../Components/layout/Headline2.vue';
const props = defineProps(['projects', 'audits']);
const newProject = ref({
  name: '',
  description: '',
});

let nameError = false;
const showHistory = ref(false);
sessionStorage.clear();

let projectsComputed = ref(props.projects);

const addProject = async () => {
  nameError = false;
  try {
    // Validate the project details. For example, check if the name is empty.
    if (!newProject.value.name) {
      nameError = true;
      usePage().props.flash.message = 'Project name is required.';
      console.error('Project name is required.');
      return;
    }

    // Make the API call to create a new project.
    const response = await axios.post('/projects/new', newProject.value);

    // Check if the API call was successful.
    if (response.data.success) {
      // Optionally, clear the form.
      newProject.value.name = '';
      newProject.value.description = '';

      usePage().props.flash.message = response.data.message;

      if (response.data.project) {
        // Add the new project to the projects list
        projectsComputed.value.push(response.data.project);
      }

      setTimeout(() => {
        window.location = route('project.show', response.data.project.id);
      }, 1000);
    } else {
      console.error('Failed to create a new project:', response.data.message);
    }
  } catch (error) {
    console.error('An error occurred while creating the project:', error);
  }
};
</script>
