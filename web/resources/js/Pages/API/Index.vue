<script setup>
import ApiTokenManager from '../../Pages/API/Partials/ApiTokenManager.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineProps({
  tokens: Array,
  availablePermissions: Array,
  defaultPermissions: Array,
});
</script>

<template>
  <AuthenticatedLayout title="API Tokens">
    <template #menu>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        API Tokens
      </h2>

      <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
          <ApiTokenManager
            :tokens="tokens"
            :available-permissions="availablePermissions"
            :default-permissions="defaultPermissions"
          />
        </div>
      </div>
    </template>
  </AuthenticatedLayout>
</template>
