<template>
  <div>
    <component
      :is="props.menu"
      title="List View Options"
      :show="props.showMenu"
      @close="API.setShowMenu(false)"
    >
      <ul class="p-4 flex flex-col gap-4">
        <li class="flex justify-between items-center">
          <label class="text-left text-xs font-medium uppercase">
            Show Sources with no Selections
          </label>
          <input type="checkbox" v-model="options.showEmpty" />
        </li>
      </ul>
    </component>
    <div class="block w-full">
      <ActivityIndicator v-if="rebuilding">
        Building list of selections...
      </ActivityIndicator>
      <div
        v-for="(source, index) in $props.sources"
        :class="
          API.cn(
            'my-2 border-l',
            hoverSources[source.id] ? 'border-l-secondary' : 'border-l-border'
          )
        "
        :key="`${source.id}-${index}`"
      >
        <div
          v-if="
            !!$props.checkedSources.get(source.id) &&
            (codesList.get(source.id)?.length || options.showEmpty)
          "
          @mouseenter="hoverSources[source.id] = true"
          @mouseleave="hoverSources[source.id] = false"
        >
          <Headline3 class="ms-3 flex justify-between">
            <button
              class="flex items-center gap-1"
              @click="
                sourcesCollapsed[source.id] = !sourcesCollapsed[source.id]
              "
            >
              <ChevronRightIcon
                :class="
                  API.cn(
                    'w-4 h-4 transition-all duration-300 transform',
                    sourcesCollapsed[source.id] && 'rotate-90'
                  )
                "
              />
              <span>{{ source.name }}</span>
            </button>
            <button
              title="Hide this source"
              @click="$emit('remove', source.id)"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </Headline3>
          <Collapse :when="sourcesCollapsed[source.id]">
            <div v-if="sourcesCollapsed[source.id]">
              <div
                v-for="code in codesList.get(source.id)"
                :key="code.id"
                :style="{ borderColor: code.color }"
                class="border ml-2 mt-3 rounded-sm"
              >
                <div
                  class="p-2 text-sm flex justify-between items-center"
                  :style="{ backgroundColor: code.color }"
                >
                  <button
                    @click="
                      codesCollapsed[`${source.id}-${code.id}`] =
                        !codesCollapsed[`${source.id}-${code.id}`]
                    "
                    class="flex gap-1 items-center"
                  >
                    <ChevronRightIcon
                      :class="
                        API.cn(
                          'w-4 h-4 transition-all duration-300 transform',
                          codesCollapsed[`${source.id}-${code.id}`] &&
                            'rotate-90'
                        )
                      "
                    />
                    <span class="bg-surface px-1 rounded-sm">{{
                      code.name
                    }}</span>
                  </button>
                  <div class="flex items-center gap-1">
                    <BarsArrowDownIcon class="h-5 w-5" />
                    <span>{{
                      selectionCounts[`${source.id}-${code.id}`] ?? 0
                    }}</span>
                  </div>
                </div>
                <Collapse :when="codesCollapsed[`${source.id}-${code.id}`]">
                  <div v-if="codesCollapsed[`${source.id}-${code.id}`]">
                    <div
                      v-for="selection in code.text"
                      :key="selection.source_id"
                    >
                      <div
                        v-if="source.id === selection.source_id"
                        class="p-1 border-t"
                        :style="{ borderTopColor: code.color }"
                      >
                        <div
                          class="text-sm text-foreground min-w-[6rem] flex justify-between p-2"
                        >
                          <div class="font-mono">
                            {{ selection.start }}:{{ selection.end }}
                          </div>
                          <span>
                            <span>{{
                              new Date(selection.updatedAt).toLocaleDateString()
                            }}</span
                            ><span>, </span>
                            <span
                              >by
                              {{
                                API.getMemberBy(selection.createdBy)?.name
                              }}</span
                            >
                          </span>
                        </div>
                        <div class="px-2 flex-grow overflow-x-scroll text-sm">
                          {{ selection.text }}
                        </div>
                      </div>
                    </div>
                  </div>
                </Collapse>
              </div>
            </div>
          </Collapse>
          <div
            v-if="!codesList.has(source.id)"
            class="ml-2 mt-2 p-2 bg-silver-100"
          >
            No selections found in this source
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch, inject, nextTick, onUnmounted } from 'vue';
import {
  BarsArrowDownIcon,
  ChevronRightIcon,
  XMarkIcon,
} from '@heroicons/vue/24/solid';

defineEmits(['remove']);
const props = defineProps([
  'sources',
  'codes',
  'checkedSources',
  'checkedCodes',
  'hasSelections',
  'menu',
  'showMenu',
]);
const codesList = ref(new Map());
const API = inject('api');
const { Headline3, ActivityIndicator, Collapse } = inject('components');
const rebuilding = ref(false);
const options = ref({
  showEmpty: false,
});
const sourcesCollapsed = ref({});
const codesCollapsed = ref({});
const selectionCounts = ref({});
const hoverSources = ref({});
const rebuildList = API.debounce(() => {
  props.sources.forEach((source) => {
    const codes = API.getCodesForSource(source);
    if (codes.length) {
      codesList.value.set(source.id, codes);
      codes.forEach((code) => {
        selectionCounts.value[`${source.id}-${code.id}`] = 0;
        (code.text ?? []).forEach((selection) => {
          if (selection.source_id === source.id) {
            selectionCounts.value[`${source.id}-${code.id}`] += 1;
          }
        });
      });
    } else {
      codesList.value.delete(source.id);
    }
  });
}, 300);

const runRebuilding = async () => {
  rebuilding.value = true;
  await nextTick();
  rebuildList();
  await nextTick();
  rebuilding.value = false;
};

watch(props, runRebuilding, { deep: true });

onMounted(runRebuilding);
onUnmounted(() => {
  sourcesCollapsed.value = {};
  codesCollapsed.value = {};
  selectionCounts.value = {};
});
</script>

<style scoped></style>
