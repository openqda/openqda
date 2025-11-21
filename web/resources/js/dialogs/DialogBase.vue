<script setup lang="ts">
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionRoot,
  TransitionChild,
} from '@headlessui/vue';
import { XMarkIcon } from "@heroicons/vue/24/solid";

/*-----------------------------------------------------------------------------
 | This is styled version of the headlessui dialog component using our theme
 | variables and conventions.
 | It's opinionated in its layout and animation and flexible
 | in its content.
 *---------------------------------------------------------------------------*/

defineProps({
  title: String,
  description: { type: String, formType: 'textarea', required: false },
  show: { type: Boolean, required: false },
  static: { type: Boolean, required: false },
  showCloseButton: { type: Boolean, required: false },
});
const emit = defineEmits(['close']);
const close = () => {
  emit('close');
};
</script>

<template>
  <TransitionRoot as="template" :show="show">
    <Dialog class="relative z-60" :static="$props.static">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div
          class="fixed inset-0 bg-foreground/50 dark:bg-background/50 transition-opacity"
        />
      </TransitionChild>

      <div class="fixed inset-0 z-60 w-screen overflow-y-auto">
        <div
          class="flex min-h-full items-start justify-center p-4 text-center"
          @click="!$props.static && close()"
        >
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 translate-y-0 scale-95"
            enter-to="opacity-100 translate-y-0 scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 scale-100"
            leave-to="opacity-0 translate-y-0 scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-surface text-left shadow-xl transition-all my-8 w-full max-w-xl">
              <div class="bg-surface px-4 pb-4 pt-5">
                <div class="flex items-start grow">
                  <slot name="icon"></slot>
                  <div
                    class="ml-4 mt-0 text-left grow"
                  >
                    <DialogTitle
                      as="h3"
                      class="text-base font-semibold leading-6 text-foreground flex justify-between items-center"
                    >
                      <span class="grow">{{ title }}<slot name="title" /></span>
                      <span v-if="showCloseButton">
                          <button @click="close" title="Close/cancel dialog" type="button">
                              <XMarkIcon class="w-4 h-4 text-foreground/60 hover:text-foreground font-bold" />
                          </button>
                      </span>
                      <span v-else><slot name="close" /></span>
                    </DialogTitle>
                    <div class="mt-2">
                      <slot name="body"></slot>
                    </div>
                  </div>
                </div>
              </div>
              <div
                class="bg-background py-3 flex flex-row-reverse px-6"
              >
                <slot name="footer"></slot>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<style scoped></style>
