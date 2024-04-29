<template>
    <div v-if="!localAudits || localAudits.length === 0" class="text-center py-4">
        <p>No history to display.</p>
    </div>

    <div v-else
         :class="context === 'homePage' ? 'flow-root w-full ml-auto mr-auto' :  'flow-root md:w-3/4 lg:w-1/2 ml-auto mr-auto' ">
        <div class="pb-1.5 mt-2 rounded-md">

            <!-- First line: Search Input -->
            <div class="flex items-center gap-x-1.5 mb-2">
                <input v-model="searchQuery" placeholder="Search..." type="text"
                       name="searchQuery"
                       id="searchQuery"
                       class=" block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                />
            </div>
            <!-- Date Filters -->
            <div class="flex justify-center space-x-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Before</label>
                    <input type="date" v-model="beforeDate"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">After</label>
                    <input type="date" v-model="afterDate"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Between</label>
                    <div class="flex space-x-2">
                        <input type="date" v-model="startDate"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <input type="date" v-model="endDate"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>
            </div>


            <!-- Second line: Filters -->
            <div class="filters flex space-x-4 w-full justify-center">
                <label class="flex items-center">
                    <Checkbox v-model="filterDocuments" :checked="filterDocuments" class="mr-2"></Checkbox>
                    Documents <span class="text-xs text-gray-500">({{ documentCount }})</span>
                </label>
                <label class="flex items-center">
                    <Checkbox v-model="filterSelections" :checked="filterSelections" class="mr-2"></Checkbox>
                    Selections <span class="text-xs text-gray-500">({{ selectionCount }})</span>
                </label>
                <label class="flex items-center">
                    <Checkbox v-model="filterCodes" :checked="filterCodes" class="mr-2"></Checkbox>
                    Codes <span class="text-xs text-gray-500">({{ codeCount }})</span>
                </label>
                <label class="flex items-center">
                    <Checkbox v-model="filterProjects" :checked="filterProjects" class="mr-2"></Checkbox>
                    Projects <span class="text-xs text-gray-500">({{ projectCount }})</span>
                </label>
                <label class="flex items-center">
                    <Checkbox v-model="filterCodebooks" :checked="filterCodebooks" class="mr-2"></Checkbox>
                    Codebooks <span class="text-xs text-gray-500">({{ codebookCount }})</span>
                </label>
            </div>


        </div>


        <ul role="list" class="-mb-8 mt-4">
            <!-- Loop through audit logs -->

            <li v-for="(audit, auditIdx) in filteredAudits.data || []" :key="audit.id">
                <div class="relative pb-8">
                                <span v-if="auditIdx !== filteredAudits.data.length - 1"
                                      class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-gray-200"
                                      aria-hidden="true">
                                </span>
                    <div class="relative flex items-start space-x-3">
                        <div>
                            <div class="relative px-1">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 ring-8 ring-white">
                                    <div v-if="audit.user_profile_picture"
                                         class="flex text-sm relative overflow-hidden transition w-8 h-8 border-2 border-transparent focus:outline-none focus:border-gray-300"
                                    >
                                        <img
                                            class="object-cover w-full h-auto rounded-full"
                                            :src="audit.user_profile_picture"
                                            :alt="audit.user_id"/>
                                    </div>
                                    <UserCircleIcon v-else class="h-5 w-5 text-gray-500" aria-hidden="true"/>
                                </div>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1 py-1">
                            <div class="text-sm text-gray-500">
                <span
                    class="font-medium text-gray-900">
                  {{ audit.user_id }}
                </span>
                                performed at {{ audit.created_at }}
                            </div>
                            <div class="mt-2 text-sm text-gray-700">
                                <ul>

                                    <template v-if="audit.event !== 'deleted'">
                                        <li v-for="(value, key) in audit.new_values" :key="key">
                                            <template v-if="audit.event === 'created'">
                                                <span v-if="audit.model === 'Codebook' && key === 'description' && value === null"></span>
                                                <span v-else>Created <span class="font-semibold text-porsche-400">{{ audit.model }}</span> with <span class="font-semibold">{{ key }}</span> set to </span>
                                                <span class="px-1 rounded font-semibold font-mono"
                                                    v-if="audit.model === 'Code'"
                                                    :style="'background-color:'+value">{{value }}</span>
                                                <span v-else-if="audit.model === 'Selection'" class="font-semibold font-mono">
                                                    {{ value.length > 50 ? value.substring(0, 50) + '...' : value }}
                                                </span>
                                                <span v-else-if="audit.model === 'Codebook' && value !== ''">
                                                    <span v-if="key === 'properties'">
                                                        public: {{ JSON.parse(value).sharedWithPublic }}
                                                    </span>
                                                    <span v-else class="font-semibold font-mono">
                                                        {{ value }}
                                                    </span>
                                                </span>
                                                <span v-else class="font-semibold font-mono">{{ value }}</span>
                                            </template>
                                            <template v-else-if="audit.event === 'updated'">
                                                <span v-if="key === 'team_id'">Created team</span>
                                                <span v-if="audit.model === 'Codebook' && key === 'description'  && value === null"></span>
                                                <span v-else>Modified {{ audit.model }} {{
                                                        key
                                                    }} to
                                                        <span v-if="audit.model === 'Code'"
                                                              :style="'background-color:'+value">{{
                                                                value
                                                            }}</span>
                                                        <span v-if="audit.model === 'Codebook'">
                                                            <span v-if="isJSON(value) === true">
                                                              public: {{ JSON.parse(value).sharedWithPublic }}
                                                            </span>
                                                            <span v-else>
                                                              {{ value }}
                                                            </span>
                                                        </span>
                                                        <span v-else>{{ value }}</span></span>
                                            </template>
                                            <template v-else-if="audit.event === 'content updated'">
                                                {{ value }}
                                            </template>
                                            <template v-else>
                                                <!-- Handle other cases -->
                                                {{ audit.event }} on {{ audit.model }} for key {{ key }}
                                                with value {{ value }}
                                            </template>
                                        </li>
                                    </template>
                                    <template v-else-if="audit.event === 'deleted'">
                                        <!-- Directly loop on old_values for 'deleted' events -->
                                        <li>
                                            {{ audit.old_values.name }} was deleted
                                        </li>
                                    </template>
                                </ul>

                            </div>

                        </div>
                    </div>
                </div>
            </li>
            <Button
                v-if="localAudits.current_page < localAudits.last_page"
                @click="loadMore"
                color="cerulean"
                :icon="ChevronDownIcon"
                class="mt-2"
                label="Load more"
            />


        </ul>
    </div>

</template>

<script setup>
import {computed, onMounted, ref, watch} from "vue";
import {ChevronDownIcon, UserCircleIcon} from "@heroicons/vue/20/solid";
import Button from "../interactive/Button.vue";
import Checkbox from "../Checkbox.vue";

const currentPage = ref(1);
const localAudits = ref({data: []});

const searchQuery = ref("");

const props = defineProps({
    audits: Object,
    projectId: {
        type: String,
        default: null,  // or an empty string '' if you prefer
    },
    context: String
});


const beforeDate = ref(null);
const afterDate = ref(null);
const startDate = ref(null);
const endDate = ref(null);


const filterDocuments = ref(true);  // 'sources'
const filterSelections = ref(true);  // 'Selection'
const filterCodes = ref(true);  // 'Code'
const filterProjects = ref(true);  // 'Project'
const filterCodebooks = ref(true);  // 'Codebooks'

const documentCount = computed(() => {
    return localAudits.value.data.filter(audit => audit.model === 'Source').length;
});
const selectionCount = computed(() => {
    return localAudits.value.data.filter(audit => audit.model === 'Selection').length;
});
const codeCount = computed(() => {
    return localAudits.value.data.filter(audit => audit.model === 'Code').length;
});
const projectCount = computed(() => {
    return localAudits.value.data.filter(audit => audit.model === 'Project').length;
});

const codebookCount = computed(() => {
    return localAudits.value.data.filter(audit => audit.model === 'Codebook').length;
});

function parseCustomDate(dateString) {
    const parts = dateString.split(' ');
    const dateParts = parts[0].split('.');
    const timeParts = parts[1].split(':');
    const year = dateParts[2];
    const month = dateParts[1] - 1; // Month is 0-indexed in JavaScript Date
    const day = dateParts[0];
    const hours = timeParts[0];
    const minutes = timeParts[1];

    return new Date(year, month, day, hours, minutes);
}

function isJSON(str) {
    try {
        JSON.parse(str);
        return true;
    } catch (e) {
        return false;
    }
}

const filteredAudits = computed(() => {
    const query = searchQuery.value.toLowerCase();

    // Make a shallow copy of the original object
    const auditCopy = {...localAudits.value};

    // Filter only the data array based on model type filters and date filters
    auditCopy.data = auditCopy.data.filter(audit => {
        // Apply model type filters here
        if (
            (audit.model === 'Source' && !filterDocuments.value) ||
            (audit.model === 'Selection' && !filterSelections.value) ||
            (audit.model === 'Code' && !filterCodes.value) ||
            (audit.model === 'Project' && !filterProjects.value) ||
            (audit.model === 'Codebook' && !filterProjects.value)
        ) {
            return false;
        }

        // Apply date filtering logic
        const auditTimestamp = parseCustomDate(audit.created_at).getTime();
        let dateFilterPass = true;

        if (beforeDate.value) {
            const beforeTimestamp = new Date(beforeDate.value).getTime();
            dateFilterPass = dateFilterPass && auditTimestamp <= beforeTimestamp;
        }

        if (afterDate.value) {
            const afterTimestamp = new Date(afterDate.value).getTime();
            dateFilterPass = dateFilterPass && auditTimestamp >= afterTimestamp;
        }

        if (startDate.value && endDate.value) {
            const startTimestamp = new Date(startDate.value).getTime();
            const endTimestamp = new Date(endDate.value).getTime();
            dateFilterPass = dateFilterPass && (auditTimestamp >= startTimestamp && auditTimestamp <= endTimestamp);
        }

        return dateFilterPass;
    });

    // Apply text search query filter
    auditCopy.data = auditCopy.data.filter(audit => {
        const newValuesStr = audit.new_values?.message || '';
        const oldValuesStr = audit.old_values?.message || '';

        return (
            audit.created_at.toLowerCase().includes(query) ||
            newValuesStr.toLowerCase().includes(query) ||
            oldValuesStr.toLowerCase().includes(query) ||
            audit.event.toLowerCase().includes(query) ||
            audit.model.toLowerCase().includes(query) ||
            audit.user_id.toLowerCase().includes(query)
        );
    });

    return auditCopy;
});


// Watch for changes in props.audits and update localAudits accordingly
watch(() => props.audits, (newVal) => {
    localAudits.value = newVal;
});

const loadMore = async () => {
    localAudits.value.current_page += 1;

    let apiUrl = `projects/load-more-audits?page=${localAudits.value.current_page}`; // default for homepage

    // If the context is projectPage, adjust the apiUrl
    if (props.context === 'projectPage') {
        apiUrl = `/projects/${props.projectId}/load-more-audits?page=${localAudits.value.current_page}`;
    }

    try {
        const response = await axios.get(apiUrl);
        console.log(response)
        const newAudits = response.data.audits.data;
        // Convert object to array
        const newAuditsArray = Object.values(newAudits);

// Merge arrays
        if (localAudits.value && Array.isArray(localAudits.value.data)) {
            localAudits.value.data = [...localAudits.value.data, ...newAuditsArray];
        } else {

            if (localAudits.value) {
                localAudits.value.data = newAudits;
            } else {
                localAudits.value = {data: newAudits};
            }
        }


        // If you need to notify the parent component, you can emit an event
        // $emit('update:audits', localAudits.value);
    } catch (error) {
        console.error('An error occurred:', error);
    }
};

onMounted(() => {
    localAudits.value = props.audits;

});

</script>

<style scoped>

</style>
