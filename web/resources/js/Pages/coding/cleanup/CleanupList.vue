<script setup lang="ts">
import Button from '../../../Components/interactive/Button.vue';
import Headline3 from '../../../Components/layout/Headline3.vue';
import { TrashIcon } from '@heroicons/vue/24/solid';
import { useCleanup } from './useCleanup';
import { attemptAsync } from '../../../Components/notification/attemptAsync';

const { remove } = useCleanup();
const props = defineProps({
  entries: Array,
  title: String,
});
const variants = {
  undefined: 'default',
  delete: 'destructive',
  create: 'confirmative',
};

const handleRemove = async (fn) => {
  const toRemove = await attemptAsync(async () => {
    const { response, error, id } = await fn();
    if (error) throw error;
    if (response.status >= 400)
      throw new Error(`Failed: ${response.data?.message}`);
    return id;
  }, 'Action successful');
  if (toRemove) remove(toRemove);
};
</script>

<template>
  <div class="overflow-x-auto">
    <Headline3 class="mb-4">{{ props.title }}</Headline3>
    <table class="table-auto border-collapse min-w-full">
      <thead>
        <tr>
          <th class="text-left">Name</th>
          <th class="text-left">Reason</th>
          <th class="text-left">Reference</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="entry in props.entries" :key="entry.id">
          <td>{{ entry.name }}</td>
          <td>{{ entry.reason }}</td>
          <td>{{ entry.ref }}</td>
          <td class="text-right">
            <Button
              v-for="(action, i) in entry.actions"
              :key="i"
              size="sm"
              :variant="variants[action.type]"
              @click="() => handleRemove(action.fn)"
              :title="action.name"
            >
              <TrashIcon class="w-4 h-4" />
            </Button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped></style>
