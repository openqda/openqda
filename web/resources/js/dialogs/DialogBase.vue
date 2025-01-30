<script setup lang="ts">
/*-----------------------------------------------------------------------------
 | This is styled version of the headlessui dialog component using our theme
 | variables and conventions.
 | It's opinionated in its layout and animation and flexible
 | in its content.
 *---------------------------------------------------------------------------*/
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionRoot,
  TransitionChild,
} from '@headlessui/vue';

defineProps({
  title: String,
  description: { type: String, formType: 'textarea', required: false },
  show: { type: Boolean, required: false },
});
const emit = defineEmits(['close']);
const close = () => {
  emit('close');
};
</script>

<template>
  <TransitionRoot as="template" :show="show">
    <Dialog class="relative z-50">
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
          class="fixed inset-0 bg-foreground/75 dark:bg-background/75 transition-opacity"
        />
      </TransitionChild>

      <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
        <div
          class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
          @click="close"
        >
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel
              class="relative transform overflow-hidden rounded-lg bg-surface text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
            >
              <div class="bg-surface px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start flex-grow">
                  <slot name="icon"></slot>
                  <div
                    class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left flex-grow"
                  >
                    <DialogTitle
                      as="h3"
                      class="text-base font-semibold leading-6 text-foreground flex justify-between items-center"
                    >
                      <span class="flex-grow"
                        >{{ title }}<slot name="title"
                      /></span>
                      <span><slot name="close" /></span>
                    </DialogTitle>
                    <div class="mt-2">
                      <slot name="body"></slot>
                    </div>
                  </div>
                </div>
              </div>
              <div
                class="bg-background px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6"
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
