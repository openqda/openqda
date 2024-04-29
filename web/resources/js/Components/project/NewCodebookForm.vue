<template>
        <div @click="toggleAccordion" class="flex items-center justify-between cursor-pointer">
            <h1 class="font-bold text-lg text-porsche-400 my-2">Create new Codebook</h1>
            <svg :class="{'rotate-90': isAccordionOpen}" xmlns="http://www.w3.org/2000/svg"
                 class="h-6 w-6 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
        <div v-if="isAccordionOpen">
            <div class="my-4">
                <div class="flex items-center">
                    <InputLabel class="w-1/4" value="Name"></InputLabel>
                    <div class="w-3/4">
                        <input id="name" name="name" type="name" autocomplete="name" v-model="codebookName"
                               class="block w-full rounded-md border-0 px-2 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-cerulean-700 sm:text-sm sm:leading-6"
                               :class="{'border-2 border-red-700': errors.name}"
                        />
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <InputLabel value="Description"></InputLabel>
                <div class="mt-2">
                            <textarea id="Description" name="Description" rows="3" v-model="codebookDesc"
                                      class="block w-full rounded-md border-0  py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-cerulean-700 sm:text-sm sm:leading-6"/>
                </div>
            </div>
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="radio" id="create-not-shared" value="create-not-shared" v-model="createSharedOption"
                           class="h-4 w-4 text-cerulean-700 focus:ring-cerulean-700 border-gray-300 rounded">
                    <label for="create-not-shared" class="ml-2 text-sm text-gray-700">Not Shared</label>
                </div>
                <div class="flex items-center my-2">

                    <input type="radio" id="create-shared-with-public" value="public" v-model="createSharedOption"
                           class="h-4 w-4 text-cerulean-700 focus:ring-cerulean-700 border-gray-300 rounded">
                    <label for="create-shared-with-public" class="ml-2 text-sm text-gray-700">Shared with
                        Public</label>
                </div>

                <div class="w-full italic text-gray-400 my-4 text-sm ">
                    When you set a codebook "public", anyone can import it on their project.
                </div>
            </div>
            <div class="flex justify-end space-x-2">
                <button @click="createCodebook"
                        class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-cerulean-700 text-base font-medium text-white hover:bg-cerulean-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cerulean-700 sm:text-sm">
                    Create
                </button>
            </div>
        </div>
</template>
<script setup>
import InputLabel from "../InputLabel.vue";
import {onMounted, ref} from "vue";

const props = defineProps([
    "project",
]);
const createSharedOption = ref("create-not-shared");
const isAccordionOpen = ref(true);
const codebookName = ref("");
const codebookDesc = ref("");
const emits = defineEmits(['codebookCreated']);
let errors = ref([]);
const toggleAccordion = () => {
    isAccordionOpen.value = !isAccordionOpen.value;
};

async function createCodebook() {

    errors.value = [];
    if (codebookName.value === '') {
        errors.value['name'] = 'Please enter a name for the codebook';
        console.log(errors.value['name']);
        return;
    }

    try {
        const response = await axios.post('/projects/' + props.project + '/codebooks', {
            name: codebookName.value,
            description: codebookDesc.value,
            sharedWithPublic: createSharedOption.value === 'public',
            sharedWithTeams: createSharedOption.value === 'teams',
        });
        let codebook = response.data.codebook;
        codebook.codes = []

        // Emit event to parent component on success
        emits('codebookCreated', codebook);

        // Reset form fields
        codebookName.value = '';
        codebookDesc.value = '';
        createSharedOption.value = '';
    } catch (error) {
        console.error('Error creating codebook:', error);
        // Handle error response...
    }
}

onMounted(() => {
    errors.value['name'] = '';
});

</script>
<style scoped>
.rotate-90 {
    transform: rotate(90deg);
}

.transition-transform {
    transition: transform 0.3s ease;
}
</style>
