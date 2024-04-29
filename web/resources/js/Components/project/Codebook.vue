<template>
    <div>
        <!-- Edit Codebook Dialog -->
        <div v-if="showDialog" class="fixed z-50 inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
            <!-- ... Modal contents ... -->
            <div
                class="z-50 bg-white rounded px-4 pt-5 pb-4 shadow-xl text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <!-- Form for editing the codebook -->
                <div class="mb-4">
                    <label for="codebook-name" class="block text-sm font-medium text-gray-700">Name:</label>
                    <input type="text" id="codebook-name" v-model="editableName"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-cerulean-700 focus:border-cerulean-700"/>
                </div>
                <div class="mb-4">
                    <label for="codebook-description"
                           class="block text-sm font-medium text-gray-700">Description:</label>
                    <textarea id="codebook-description" v-model="editableDescription" rows="3"
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-cerulean-700 focus:border-cerulean-700"></textarea>
                </div>
                <div class="mb-4">
                    <div class="flex items-center">
                        <input type="radio" id="not-shared" value="update-not-shared" v-model="sharedOption"
                               class="h-4 w-4 text-cerulean-700 focus:ring-cerulean-700 border-gray-300 rounded">
                        <label for="not-shared" class="ml-2 text-sm text-gray-700">Not Shared</label>
                    </div>
                    <div class="flex items-center mt-2">
                        <input type="radio" id="update-shared-with-public" value="update-public" v-model="sharedOption"
                               class="h-4 w-4 text-cerulean-700 focus:ring-cerulean-700 border-gray-300 rounded">
                        <label for="update-shared-with-public" class="ml-2 text-sm text-gray-700">Shared with
                            Public</label>
                    </div>

                </div>
                <div class="w-full italic text-gray-400 mb-4 text-sm ">
                    When you set a codebook "public", anyone can import it on their project. When you set a codebook
                    "shared with your teams", only members of your teams can import it on their projects.
                </div>

                <!-- Action buttons -->
                <div class="flex justify-end space-x-2">

                    <button @click="updateCodebook"
                            class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-cerulean-700 text-base font-medium text-white hover:bg-cerulean-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cerulean-700 sm:text-sm">
                        Save
                    </button>
                    <button @click="closeDialog"
                            class="mt-3 inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cerulean-700 sm:mt-0 sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
        <div class="flex">
            <div
                class="flex w-16 flex-shrink-0 items-center justify-center rounded-tl-md  text-sm font-medium text-white"
                :style="getBackgroundStyle(codebook)">

                {{
                    getInitials(codebook.name)
                }}
            </div>
            <div
                class="relative flex-grow flex items-center justify-between rounded-tr-md truncate border-b border-r border-t border-gray-200 bg-white">
                <div class="flex-1 truncate px-4 py-2 text-sm">
                    <a :title="codebook.name" :href="codebook.href"
                       class="font-medium text-gray-900 hover:text-gray-600">
                        {{ codebook.name }}
                    </a>
                    <p class="text-gray-500 italic">{{ codebook.description }}</p>
                    <p class="text-gray-500 text-xs">{{ codebook.creatingUserEmail }}</p>
                    <p class="text-gray-500">{{ codebook.codes && codebook.codes.length ? codebook.codes.length : 0 }}
                        Codes</p>
                    <div class="flex items-center space-x-2 font-mono  align-center">
                        <div class="flex items-center">
                            <div class="w-2 h-2 rounded-full mr-1 shadow-xl box-decoration-slice"
                                 :class="codebook.properties?.sharedWithPublic ? 'bg-green-500 shadow-green' : 'bg-red-700 shadow-red'"></div>
                            <span
                                class="text-xs ">{{ codebook.properties?.sharedWithPublic ? 'public' : 'private' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex-shrink-0 pr-2">
                    <button
                        v-click-outside="{callback:() => codebook.showMenu = false}"
                        @click="codebook.showMenu = !codebook.showMenu"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Open options</span>
                        <EllipsisVerticalIcon class="h-5 w-5" aria-hidden="true"/>
                    </button>
                </div>
            </div>
        </div>
        <div v-if="localCodebooks.codes && codebook.showCodes"
             class="absolute w-full left-0 border-gray-200 bg-white h-96 overflow-y-auto align-middle border-b border-r border-l z-50">
            <ul>
                <li v-for="code in localCodebooks.codes" :key="code.id" class="flex items-center">
                    <div
                        class="flex w-16 h-16 flex-shrink-0 items-center justify-center text-sm font-medium text-white"
                        :style="'background-color: ' + code.color">
                        {{
                            code.name.split(' ').reduce((initials, namePart) => initials += namePart.substring(0, 1).toUpperCase(), '')
                        }}
                    </div>
                    <div class="ml-2">{{ code.name }}</div>
                </li>
            </ul>
        </div>

        <div v-show="codebook.showMenu"
             class="absolute top-0 left-auto z-50 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
             style="min-width: max-content;">
            <div class="py-1 cursor-pointer" role="menu" aria-orientation="vertical"
                 aria-labelledby="options-menu">
                <a v-if="!public" @click="editCodebook(codebook)"
                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                   role="menuitem">Edit</a>
                <a @click="codebook.showCodes = !codebook.showCodes"
                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                   role="menuitem"><span v-if="codebook.showCodes">Hide codes</span><span v-else>Show Codes</span></a>
                <a v-if="public" @click="importCodebook(codebook)"
                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                   role="menuitem">Import into project</a>
                <a v-if="!public" @click="deleteCodebook(codebook)"
                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-red-700 "
                   role="menuitem">Delete</a>
            </div>
        </div>
    </div>
</template>
<script setup>
import {EllipsisVerticalIcon,} from "@heroicons/vue/20/solid";
import {vClickOutside} from "../coding/clickOutsideDirective.js";
import {onMounted, reactive, ref} from "vue";

const props = defineProps(["codebook", 'public']);
// Reactive state for the dialog visibility and editable fields
const showDialog = ref(false);
const editableName = ref('');
const editableDescription = ref('');
const editableCodebook = ref(null);
const sharedOption = ref('update-not-shared');
const emit = defineEmits(['delete', 'importCodebook']);
// Creating a reactive copy of the codes
const localCodebooks = reactive({
    codes: [...props.codebook.codes]
});

onMounted(() => {
    localCodebooks.codes = reorderCodes(localCodebooks.codes);
});

// Method to initiate editing of a codebook
const editCodebook = (codebook) => {
    showDialog.value = true;
    // Store a reference to the original codebook
    editableCodebook.value = codebook;
    // Copy properties to editable fields
    editableName.value = codebook.name;
    editableDescription.value = codebook.description;
    // Check if properties field exists and has the sharedWithPublic and sharedWithTeams keys
    if (codebook.properties) {
        if (codebook.properties.sharedWithPublic) {
            sharedOption.value = 'update-public';
        } else if (codebook.properties.sharedWithTeams) {
            sharedOption.value = 'update-teams';
        } else {
            sharedOption.value = 'update-not-shared';
        }
    } else {
        // Default to 'update-not-shared' if properties are not set
        sharedOption.value = 'update-not-shared';
    }
};

const getInitials = (name) => {
    const words = name.split(' ');
    let initials = '';

    if (words.length === 1) {
        // If there's only one word, take the first two letters
        initials = name.substring(0, 2).toUpperCase();
    } else {
        // If there are multiple words, take the first letter of each word
        initials = words.reduce((acc, namePart) => acc += namePart.substring(0, 1).toUpperCase(), '');
    }

    // Limit the initials to the first five characters
    return initials.substring(0, 5);
};


const updateCodebook = async () => {
    try {
        const response = await axios.post(`/projects/${editableCodebook.value.project_id}/codebooks/${editableCodebook.value.id}`, {
            name: editableName.value,
            description: editableDescription.value,
            sharedWithPublic: sharedOption.value === 'update-public',
            sharedWithTeams: sharedOption.value === 'update-teams',
        });


        // Directly update the codebook in the interface
        editableCodebook.value.name = editableName.value;
        editableCodebook.value.description = editableDescription.value;
        editableCodebook.value.properties.sharedWithPublic = sharedOption.value === 'update-public';
        editableCodebook.value.properties.sharedWithTeams = sharedOption.value === 'update-teams';

        // Close the dialog on success
        showDialog.value = false;
    } catch (error) {
        console.error('Update failed:', error);
        // Handle error...
        // Existing error handling logic
    }
};

// Function to reorder codes
const reorderCodes = (codesArray) => {
    const orderedCodes = [];
    const childCodesMap = new Map();

    // First, separate codes into parents and children
    codesArray.forEach(code => {
        if (code.parent_id) {
            if (!childCodesMap.has(code.parent_id)) {
                childCodesMap.set(code.parent_id, []);
            }
            childCodesMap.get(code.parent_id).push(code);
        } else {
            orderedCodes.push(code);
        }
    });

    // Then, insert child codes immediately after their parents
    return orderedCodes.flatMap(code => [code, ...(childCodesMap.get(code.id) || [])]);
}

// Method to close the dialog without saving
const closeDialog = () => {
    showDialog.value = false;
};


async function importCodebook(codebook) {
    if (
        !confirm(
            "Would you like to import this codebook? This will import all the codes inside this project."
        )
    ) {
        return;
    }

    emit('importCodebook', codebook);


}

const deleteCodebook = async (codebook) => {
    if (
        !confirm(
            "This is an EXTREMELY destructive action. Are you sure you want to delete this codebook? This will delete ALL THE CODED TEXT."
        )
    ) {
        return;
    }

    try {
        const response = await axios.delete(`/projects/${codebook.project_id}/codebooks/${codebook.id}`, {});
        // emit delete codebook from array
        emit('delete', codebook)


        // Close the dialog on success
        showDialog.value = false;
    } catch (error) {
        console.error('Update failed:', error);
        // Handle error...

    }
};

const getBackgroundStyle = function (codebook) {
    // Check if there are any codes
    if (!codebook.codes || codebook.codes.length === 0) {
        // Return a default style for the empty rectangle
        return `background-color: #E5E7EB;`; // This is a light gray color, adjust as needed
    }

    // If there's only one code color, return a single color style
    if (codebook.codes.length === 1) {
        return `background-color: ${codebook.codes[0].color}`;
    }

    // Create a gradient with equal-sized stripes for each color
    const percentage = 100 / codebook.codes.length;
    let gradient = codebook.codes.map((code, index) =>
        `${code.color} ${index * percentage}% ${(index + 1) * percentage}%`
    ).join(', ');

    return `background: linear-gradient(to right, ${gradient})`;
};
</script>
<style scoped>
.shadow-green {
    box-shadow: 0 0 8px 0 rgba(5, 150, 105, 0.5); /* Custom green shadow */
}

.shadow-red {
    box-shadow: 0 0 8px 0 rgba(220, 38, 38, 0.5); /* Custom red shadow */
}
</style>

