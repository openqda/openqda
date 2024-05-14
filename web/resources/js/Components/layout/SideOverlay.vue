<template>
  <TransitionRoot as="template" :show="open">
    <Dialog
      as="div"
      class="relative z-10"
      @close="
        open = false
        emit('close')
      "
    >
      <div class="fixed inset-0" />

      <div class="fixed inset-0 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
          <div
            class="pointer-events-none fixed inset-y-0 flex max-w-full"
            :class="
              props.position === 'left' ? 'left-0 pr-10' : 'right-0 pl-10'
            "
          >
            <TransitionChild
              as="template"
              enter="transform transition ease-in-out duration-500 sm:duration-700"
              :enter-from="
                props.position === 'left'
                  ? '-translate-x-full'
                  : 'translate-x-full'
              "
              enter-to="translate-x-0"
              leave="transform transition ease-in-out duration-500 sm:duration-700"
              leave-from="translate-x-0"
              :leave-to="
                props.position === 'left'
                  ? '-translate-x-full'
                  : 'translate-x-full'
              "
            >
              <DialogPanel class="pointer-events-auto w-screen max-w-md">
                <div
                  class="flex h-full flex-col overflow-y-scroll"
                  :class="
                    props.transparency
                      ? 'bg-transparent'
                      : 'bg-white shadow-2xl'
                  "
                >
                  <div class="bg-cerulean-700 px-4 py-6 sm:px-6">
                    <div class="flex items-center justify-between">
                      <DialogTitle
                        class="text-base font-semibold leading-6 text-white"
                      >
                        {{ props.title }}
                      </DialogTitle>
                      <div class="ml-3 flex h-7 items-center">
                        <button
                          type="button"
                          class="relative border-0 rounded-md bg-cerulean-700 text-white hover:text-silver-900"
                          @click="open = false"
                        >
                          <span class="absolute -inset-2.5" />
                          <span class="sr-only">Close panel</span>
                          <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                        </button>
                      </div>
                    </div>
                    <div class="mt-1" v-if="props.description">
                      <p class="text-sm text-grey-300">
                        {{ props.description }}
                      </p>
                    </div>
                  </div>
                  <div class="relative flex-1">
                    <!-- Your content -->
                    <slot></slot>
                  </div>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, watch } from 'vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const emit = defineEmits(['close'])
const props = defineProps([
  'title',
  'description',
  'show',
  'position',
  'transparency',
])
const open = ref(false)

watch(
  () => props.show,
  (first, second) => {
    open.value = second
  }
)
</script>
