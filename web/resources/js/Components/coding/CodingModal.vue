<template>
  <div
    v-if="show"
    class="fixed inset-0 z-10 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
  >
    <div
      class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0"
    >
      <div
        class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
        aria-hidden="true"
      ></div>
      <span
        class="hidden sm:inline-block sm:align-middle sm:h-screen"
        aria-hidden="true"
        >&#8203;</span
      >
      <div
        :class="size"
        class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle"
      >
        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
              <h3
                class="text-lg font-medium leading-6 text-gray-900"
                id="modal-title"
              >
                {{ title }}
              </h3>
              <div class="mt-2">
                <div class="text-sm text-gray-500">
                  <div v-for="(item, index) in text" :key="index">
                    {{ item.text }}
                    <div v-if="index !== text.length - 1" class="my-2"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            type="button"
            @click="$emit('close')"
            class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-700 sm:ml-3 sm:w-auto sm:text-sm"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    title: {
      type: String,
      required: true,
    },
    text: {
      type: Array,
      required: true,
    },
    show: {
      type: Boolean,
      required: true,
    },
    size: {
      type: String,
      default: 'sm:max-w-lg sm:w-full',
    },
  },
};
</script>
