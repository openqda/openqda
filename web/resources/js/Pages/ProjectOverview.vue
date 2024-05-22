<template>
    <AppLayout>
        <Head :title="name"/>
        <div>
            <div class="sm:hidden">
                <label for="tabs" class="sr-only">Select a tab</label>
                <!-- Use an "onChange" listener to redirect the user to the selected tab URL. -->
                <select
                    id="tabs"
                    name="tabs"
                    class="block w-full border-gray-300 rounded-md focus:border-red-700 focus:ring-red-700"
                >
                    <option v-for="tab in tabs" :key="tab.name" :selected="tab.current">
                        {{ tab.name }}
                    </option>
                </select>
            </div>
            <div class="hidden sm:block">
                <div class="">
                    <nav
                        class="flex justify-center -mb-px space-x-8 border-b"
                        aria-label="Tabs"
                    >
                        <a
                            v-for="tab in tabs"
                            :key="tab.key"
                            :href="tab.href"
                            @click="toggleSubView(tab.key, $event)"
                            :class="[
                currentSubView === tab.key
                  ? 'border-porsche-400 text-porsche-500'
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                'group inline-flex items-center justify-center border-b-2 py-4 px-1 text-sm font-medium',
              ]"
                            :aria-current="tab.current ? 'page' : undefined"
                        >
                            <component
                                :is="tab.icon"
                                :class="[
                  currentSubView === tab.key
                    ? 'text-porsche-500'
                    : 'text-gray-400 group-hover:text-gray-500',
                  '-ml-0.5 mr-2 h-5 w-5',
                ]"
                                aria-hidden="true"
                            />
                            <span>{{ tab.name }}</span>
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <div class="flex flex-col w-full h-screen pt-5 ml-auto mr-auto lg:w-3/4">
            <div v-show="currentSubView === 'overview'">
                <div class="mt-6 border-t border-gray-100">
                    <dl class="divide-y divide-gray-100">
                        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">
                                Project name
                            </dt>
                            <dd
                                class="flex mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
                            >
                                <!-- contenteditable and @blur added here -->
                                <span
                                    class="flex-grow"
                                    ref="projectName"
                                    contenteditable="true"
                                    @keydown.enter="updateProject('name', $event)"
                                >
                  {{ project.name }}
                </span>
                                <button
                                    type="button"
                                    @click="updateProject('name')"
                                    class="font-medium text-cerulean-700 bg-white rounded-md hover:text-cerulean-700"
                                >
                                    Update
                                </button>
                            </dd>
                        </div>
                        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">
                                Project description
                            </dt>
                            <dd
                                class="flex mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
                            >
                                <!-- contenteditable and @blur added here -->
                                <span
                                    class="flex-grow"
                                    ref="projectDescription"
                                    contenteditable="true"
                                    @keydown.enter="updateProject('description', $event)"
                                >
                  {{ description }}
                </span>
                                <button
                                    type="button"
                                    @click="updateProject('description')"
                                    class="font-medium text-cerulean-700 bg-white rounded-md hover:text-cerulean-700"
                                >
                                    Update
                                </button>
                            </dd>
                        </div>
                        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-red-700">
                                Danger zone
                            </dt>
                            <dd
                                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
                            >
                                <ul
                                    role="list"
                                    class="border border-gray-200 divide-y divide-gray-100 rounded-md"
                                >
                                    <li
                                        class="flex items-center justify-between py-4 pl-4 pr-5 text-sm leading-6"
                                    >
                                        <div class="flex items-center flex-1 w-0">
                                            <ExclamationTriangleIcon
                                                class="flex-shrink-0 w-5 h-5 text-gray-400"
                                                aria-hidden="true"
                                            />
                                            <div class="flex flex-1 min-w-0 gap-2 ml-4">
                        <span class="font-medium truncate"
                        >Delete this project</span
                        >
                                                <span class="flex-shrink-0 text-gray-400">
                          This cannot be undone!
                        </span>
                                            </div>
                                        </div>
                                        <div class="flex flex-shrink-0 ml-4 space-x-4">
                                            <button
                                                type="button"
                                                @click="deleteProject"
                                                class="font-medium text-red-700 bg-white rounded-md hover:text-red-800"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </li>
                                </ul>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div v-show="currentSubView === 'codebooks'" class="space-y-4">
                <div class="w-1/2">
                    <NewCodebookForm
                        :project="projectId"
                        @codebookCreated="onCodebookCreated"
                    />
                </div>
                <div>
                    <Headline2>Import Codebook XML</Headline2>
                    <form @submit.prevent="importXmlFile" enctype="multipart/form-data">
                        <input
                            type="file"
                            @change="handleFileUpload"
                            accept=".qde,.xml,.qdc"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100"
                        />
                        <button
                            type="submit"
                            class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        >
                            Import
                        </button>
                    </form>
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
            </div>
            <div v-show="currentSubView === 'collab'">
                <CreateTeamForm :projectId="projectId" v-if="!hasTeam"/>
                <div v-if="hasTeam">
                    <Headline2>My Project Team</Headline2>

                    <div>
                        <div class="py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
                            <UpdateTeamNameForm :team="team" :permissions="permissions"/>

                            <TeamMemberManager
                                class="mt-10 sm:mt-0"
                                :team="team"
                                :available-roles="availableRoles"
                                :user-permissions="permissions"
                                :team-owner="teamOwner"
                                :project="project"
                            />

                            <template v-if="permissions.canDeleteTeam && !team.personal_team">
                                <SectionBorder/>

                                <DeleteTeamForm class="mt-10 sm:mt-0" :team="team"/>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div v-show="currentSubView === 'history'">
                <Audit
                    :project-id="projectId"
                    :audits="audits"
                    context="projectPage"
                ></Audit>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
/*
 |--------------------------------------------------------------------------
 | Project Overview
 |--------------------------------------------------------------------------
 | Page-level component, that represents the current project and allows
 | to manage settings, teams, codebooks and audits.
 */
import {computed, onBeforeUnmount, onMounted, ref, watch} from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import {
    ClockIcon,
    ExclamationTriangleIcon,
    PresentationChartLineIcon,
    RectangleStackIcon,
} from '@heroicons/vue/20/solid';
import {UsersIcon} from '@heroicons/vue/24/outline';
import {Head, router, usePage} from '@inertiajs/vue3';
import CreateTeamForm from './Teams/Partials/CreateTeamForm.vue';
import UpdateTeamNameForm from './Teams/Partials/UpdateTeamNameForm.vue';
import TeamMemberManager from './Teams/Partials/TeamMemberManager.vue';
import SectionBorder from '../Components/SectionBorder.vue';
import DeleteTeamForm from './Teams/Partials/DeleteTeamForm.vue';
import Audit from '../Components/global/Audit.vue';

import Codebook from '../Components/project/Codebook.vue';
import NewCodebookForm from '../Components/project/NewCodebookForm.vue';
import Headline2 from '../Components/layout/Headline2.vue';

const searchQueryPublicCodebooks = ref('');
const codebooks = ref([]);

const props = defineProps([
    'project',
    'teamMembers',
    'hasTeam',
    'availableRoles',
    'permissions',
    'team',
    'audits',
    'userCodebooks',
    'publicCodebooks',
    'hasCodebooksTab',
    'teamOwner',
]);
const isEditable = ref({title: false, description: false});
const projectName = ref('');
const projectDescription = ref('');
const name = ref(props.project.name);
const description = ref(props.project.description);
const url = window.location.pathname;
const segments = url.split('/');
let projectId = segments[2]; // Assuming project id is the third segment in URL path
const localAudits = ref([]);
// File handling for importing XML
const selectedFile = ref(null);

const filteredPublicCodebooks = computed(() => {
    return props.publicCodebooks.filter((codebook) =>
        codebook.name
            .toLowerCase()
            .includes(searchQueryPublicCodebooks.value.toLowerCase())
    );
});
onMounted(() => {
    localAudits.value = props.audits;
    if (props.hasCodebooksTab) {
        window.location.hash = '#codebooks';
    }
    const hash = window.location.hash;
    if (hash) {
        // Find the tab that corresponds to the URL hash
        const matchedTab = tabs.value.find((tab) => `#${tab.key}` === hash);
        if (matchedTab) {
            currentSubView.value = matchedTab.key;
            // Update the 'current' property of all tabs
            tabs.value.forEach((tab) => {
                tab.current = tab.key === matchedTab.key;
            });
        }
    }

    if (typeof projectId === 'undefined') projectId = props.project.id;

    codebooks.value = props.project.codebooks;
});

// Watch for changes in props.audits and update localAudits accordingly
watch(
    () => props.audits,
    (newVal) => {
        localAudits.value = newVal;
    }
);

const onCodebookCreated = (newCodebook) => {
    // Add the new codebook to the project's codebooks array
    codebooks.value.push(newCodebook);
};

const deleteProject = async () => {
    if (
        !confirm(
            'Are you sure you want to delete this project? This cannot be undone.'
        )
    ) {
        return;
    }
    localStorage.removeItem('text');

    // try {
    router.delete(route('project.destroy', {project: projectId}));

    //     if (response.data.success) {
    //         usePage().props.flash.message = response.data.message;
    //         // Navigate the user back to the list of projects or a confirmation page
    //
    //     } else {
    //         // Handle the error response from the server
    //         console.error('Failed to delete the project:', response.data.message);
    //         usePage().props.flash.message = response.data.message;
    //     }
    // } catch (error) {
    //     // If the request fails for any reason, `response` will be undefined here
    //     console.error('An error occurred:', error);
    //     usePage().props.flash.message = 'An error occurred while deleting the project.';
    // }
};

const deleteCodebookFromArray = (codebook) => {
    const index = codebooks.value.findIndex((cb) => cb.id === codebook.id);
    if (index !== -1) {
        codebooks.value.splice(index, 1);
    }
};

const updateProject = async (field, event = null) => {
    if (event) {
        event.preventDefault(); // Prevent default Enter key behavior
    }
    let payload = {value: '', type: field};
    if (field === 'name') {
        payload.value = projectName.value.innerText;
    } else if (field === 'description') {
        payload.value = projectDescription.value.innerText;
    }

    let response;

    try {
        response = await axios.post('/projects/update/' + projectId, payload);
        if (response.data.success) {
            usePage().props.flash.message = response.data.message;
            if (field === 'name') {
                name.value = payload.value;
            } else if (field === 'description') {
                description.value = payload.value;
            }
            isEditable.value[field] = false;
        }
    } catch (error) {
        usePage().props.flash.message = response?.data?.message;
        console.error('Failed to update project:', error);
    }
};

const importCodebook = async (codebook) => {
    try {
        const response = await axios.post('/projects/' + projectId + '/codebooks', {
            name: codebook.name,
            description: codebook.description,
            sharedWithPublic: false,
            sharedWithTeams: false,
            import: true,
            id: codebook.id,
        });

        usePage().props.flash.message = response.data.message;
        router.get(
            route('project.show', {project: projectId, codebookstab: true})
        );
    } catch (error) {
        console.error('Failed to import codebook:', error);
    }
};

const currentSubView = ref('overview');
const tabs = ref([
    {
        key: 'overview',
        name: 'Overview',
        href: '#overview',
        icon: PresentationChartLineIcon,
        current: true,
    },

    {
        key: 'collab',
        name: 'Collaboration',
        href: '#collab',
        icon: UsersIcon,
        current: false,
    },
    {
        key: 'codebooks',
        name: 'Codebooks',
        href: '#codebooks',
        icon: RectangleStackIcon,
        current: false,
    },
    {
        key: 'history',
        name: 'History',
        href: '#history',
        icon: ClockIcon,
        current: false,
    },
]);

onBeforeUnmount(() => {
    localStorage.clear();
});

function toggleSubView(key) {
    currentSubView.value = key;
}

const handleFileUpload = (event) => {
    selectedFile.value = event.target.files[0];
};

const importXmlFile = async () => {
    if (!selectedFile.value) {
        alert("Please select a file first.");
        return;
    }

    const formData = new FormData();
    formData.append("file", selectedFile.value);
    formData.append("project_id", projectId);

    try {
        const response = await axios.post("/codebook/import", formData, {
            headers: {
                "Content-Type": "multipart/form-data"
            }
        });

        alert(response.data.message);
        // Refresh the page or update the relevant data
        router.get(route("project.show", {project: projectId, codebookstab: true}));
    } catch (error) {
        console.error("Failed to import XML:", error);
        alert("Failed to import XML. Please try again.");
    }
};
</script>
<style scoped>
span[contenteditable='true']:focus {
    outline: none;
    border: none;
}
</style>
