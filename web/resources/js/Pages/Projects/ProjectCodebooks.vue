<script setup lang="ts">
import Codebook from '../../Components/project/Codebook.vue';
import NewCodebookForm from '../../Components/project/NewCodebookForm.vue';
import Headline2 from '../../Components/layout/Headline2.vue';
import {request} from "../../utils/http/BackendRequest";
import {flashMessage} from "../../Components/notification/flashMessage";
import {router} from "@inertiajs/vue3";
import {computed, inject, ref} from "vue";

// File handling for importing XML
const selectedFile = ref(null);
const project = inject('project')
const userCodebooks = inject('userCodebooks')
const publicCodebooks = inject('publicCodebooks')
const codebooks = ref(project.codebooks);
const searchQueryPublicCodebooks = ref('');

const filteredPublicCodebooks = computed(() => {
    return publicCodebooks.filter((codebook) =>
        codebook.name
            .toLowerCase()
            .includes(searchQueryPublicCodebooks.value.toLowerCase())
    );
});

const onCodebookCreated = (newCodebook) => {
    // Add the new codebook to the project's codebooks array
    codebooks.value.push(newCodebook);
};

const deleteCodebookFromArray = (codebook) => {
    const index = codebooks.value.findIndex((cb) => cb.id === codebook.id);
    if (index !== -1) {
        codebooks.value.splice(index, 1);
    }
};

const importCodebook = async (codebook) => {
    const { response, error } = await request({
        url: `/projects/${project.id}/codebooks`,
        type: 'post',
        body: {
            name: codebook.name,
            description: codebook.description,
            sharedWithPublic: false,
            sharedWithTeams: false,
            import: true,
            id: codebook.id,
        },
    });
    if (error) {
        console.error('Failed to import codebook:', error);
        flashMessage(response.data.message, { type: 'error' });
    } else {
        flashMessage(response.data.message);
        router.get(
            route('project.show', { project: project.id, codebookstab: true })
        );
    }
};

const handleFileUpload = (event) => {
    selectedFile.value = event.target.files[0];
};

const importXmlFile = async () => {
    if (!selectedFile.value) {
        alert('Please select a file first.');
        return;
    }
    y;
    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('project_id', project.id);

    const { response, error } = await request({
        url: '/codebook/import',
        body: formData,
        type: 'post',
        headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (error) {
        console.error('Failed to import XML:', error);
        const message = response?.data?.error
            ? response.data.error
            : 'Failed to import XML. An unexpected error occurred.';
        flashMessage(message, { type: 'error' });
    } else {
        flashMessage(response.data.message);
        setTimeout(() => {
            // Refresh the page or update the relevant data
            router.get(
                route('project.show', {
                    project: project.id,
                    codebookstab: true,
                })
            );
        }, 1000);
    }
};

</script>

<template>
  <div class="flex space-x-4">
    <div class="w-1/2 my-2">
      <NewCodebookForm
        :project="project.id"
        @codebookCreated="onCodebookCreated"
      />
    </div>
    <div class="w-1/2 my-2">
      <Headline2>Import Codebook from</Headline2>
      <div class="w-full italic text-gray-400 my-4 text-sm">
        This import is intended for codebook exports that supports
        <a
          href="https://www.qdasoftware.org/refi-qda-codebook"
          title="REFI website"
          target="_blank"
          class="text-blue-500 font-bold"
          >REFI</a
        >.
        <br />
        Our goal is to support MaxQDA, NViVo, atlas.ti, f4analyse. If you find a
        problem, please
        <a href="mailto:openqda@uni-bremen.de" class="text-blue-500 font-bold"
          >contact us</a
        >.
        <br />
        You can also import codebooks from other projects, but they might not be
        fully functional.
      </div>
      <form
        @submit.prevent="importXmlFile"
        enctype="multipart/form-data"
        class="space-y-4"
      >
        <label class="font-medium text-sm text-gray-700 my-4 flex items-center">
          <span class="sr-only">Choose file</span>
          <input
            type="file"
            @change="handleFileUpload"
            accept=".qde,.xml,.qdc"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
          />
        </label>
        <button
          type="submit"
          class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-cerulean-700 text-base font-medium text-white hover:bg-cerulean-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cerulean-700 sm:text-sm"
        >
          Import
        </button>
      </form>
    </div>
  </div>
  <div>
    <Headline2>Codebooks of current Project</Headline2>
    <ul
      v-if="codebooks.length > 0"
      role="list"
      class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4"
    >
      <li
        v-for="codebook in codebooks"
        :key="codebook.name"
        class="col-span-1 flex flex-col relative"
      >
        <Codebook
          :codebook="codebook"
          @delete="deleteCodebookFromArray(codebook)"
        ></Codebook>
      </li>
    </ul>
    <div v-else>
      <p class="text-sm text-gray-500">You didn't create any codebook</p>
    </div>
  </div>

  <div>
    <Headline2>My created Codebooks in other Projects</Headline2>
    <ul
      v-if="userCodebooks.length > 0"
      role="list"
      class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4"
    >
      <li
        v-for="codebook in userCodebooks"
        :key="codebook.name"
        class="col-span-1 flex flex-col relative"
      >
        <Codebook
          :codebook="codebook"
          :public="true"
          @importCodebook="importCodebook(codebook)"
        ></Codebook>
      </li>
    </ul>
    <div v-else>
      <p class="text-sm text-gray-500">
        No codebooks from other projects available
      </p>
    </div>
  </div>

  <div>
    <Headline2>Public Codebooks</Headline2>
    <input
      v-if="publicCodebooks.length > 0"
      v-model="searchQueryPublicCodebooks"
      type="text"
      placeholder="Search public codebooks..."
      class="mt-2 mb-3 w-1/2 rounded-md border-gray-300 shadow-sm"
    />
    <ul
      v-if="filteredPublicCodebooks.length > 0"
      role="list"
      class="grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4"
    >
      <li
        v-for="codebook in filteredPublicCodebooks"
        :key="codebook.name"
        class="col-span-1 flex flex-col relative"
      >
        <Codebook
          :codebook="codebook"
          :public="true"
          @importCodebook="importCodebook(codebook)"
        ></Codebook>
      </li>
    </ul>
    <div v-else>
      <p class="text-sm text-gray-500">No public codebooks available</p>
    </div>
  </div>
</template>

<style scoped></style>
