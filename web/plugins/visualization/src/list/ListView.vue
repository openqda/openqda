<template>
    <div
        v-for="(source, index) in $props.sources"
        class="my-10 border-l border-l-border"
        :key="`${source.id}-${index}`"
    >
        <div
            v-if="
        !!$props.checkedSources.get(source.id) &&
        (codesList.get(source.id)?.length || options.showEmpty)
      "
        >
            <Headline3 class="ms-3">
                <span>{{ source.name }}</span>
                <button
                    title="Hide this source"
                    @click="$emit('remove', source.id)"
                    class="float-right"
                >
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </Headline3>
            <div
                v-for="code in codesList.get(source.id)"
                :key="code.id"
                :style="{ borderColor: code.color }"
                class="border-l border-r ml-2 mt-3"
            >
                <div class="p-2" :style="{ backgroundColor: code.color }">
                    <span>{{ code.name }}</span>
                </div>
                <div v-for="selection in code.text" :key="selection.source_id">
                    <div
                        v-if="source.id === selection.source_id"
                        class="my-1 border-b"
                        :style="{ borderBottomColor: code.color }"
                    >
                        <div
                            class="text-sm text-silver-500 min-w-[6rem] flex justify-between p-2"
                        >
                            <div>{{ selection.start }} - {{ selection.end }}</div>
                            <span>
                <span>{{
                        new Date(selection.updatedAt).toLocaleDateString()
                    }}</span
                ><span>, </span>
                <span>by {{ API.getMemberBy(selection.createdBy)?.name }}</span>
              </span>
                        </div>
                        <div class="p-2 flex-grow">{{ selection.text }}</div>
                    </div>
                </div>
            </div>
            <div v-if="!codesList.has(source.id)" class="ml-2 mt-2 p-2 bg-silver-100">
                No selections found in this source
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, watch, inject } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/solid';

defineEmits(['remove']);
const props = defineProps([
    'sources',
    'codes',
    'checkedSources',
    'checkedCodes',
    'hasSelections',
]);
const codesList = ref(new Map());
const API = inject('api');
const { Headline3 } = inject('components');

const options = ref({
    showEmpty: true,
});

/*
 API.defineOptions({
 showEmpty: {
 type: Boolean,
 label: 'Show uncoded',
 title: 'Show sources that have no selections',
 labelClass: 'text-nowrap',
 defaultValue: true,
 model: options.showEmpty
 },
 sort: {
 type: String,
 label: null,
 groupClass: 'inline w-20',
 defaultValue: 'selections_count',
 options: [
 { value: 'name', label: 'by name'},
 { value: 'selections_count', label: 'by number of selections'},
 ]
 }
 })
 */

const rebuildList = () => {
    props.sources.forEach((source) => {
        const c = API.getCodesForSource(source);

        if (c.length) {
            codesList.value.set(source.id, c);
        } else {
            codesList.value.delete(source.id);
        }
    });
};

watch(props, API.debounce(rebuildList, 100), { immediate: true, deep: true });

onMounted(() => {
    rebuildList();
});
</script>

<style scoped></style>
