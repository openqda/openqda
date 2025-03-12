<script setup>
import { computed, onMounted, onUnmounted, ref, defineEmits } from 'vue';
const emit = defineEmits(['open', 'close']);
const props = defineProps({
  align: {
    type: String,
    default: 'right',
  },
  width: {
    type: String,
    default: '48',
  },
  contentClasses: {
    type: Array,
    default: () => ['py-1', 'bg-white'],
  },
  prevent: {
    type: Boolean,
    default: false,
  },
});

let open = ref(false);

const closeOnEscape = (e) => {
  if (!props.prevent && open.value && e.key === 'Escape') {
    open.value = false;
    emit('close', this);
  }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const widthClass = computed(() => {
  return {
    48: 'w-48',
  }[props.width.toString()];
});

const alignmentClasses = computed(() => {
  if (props.align === 'left') {
    return 'origin-top-left left-0';
  }

  if (props.align === 'right') {
    return 'origin-top-right right-0';
  }

  return 'origin-top';
});

function handleClick() {
  if (!props.prevent) {
    open.value = !open.value;
    const name = open.value ? 'open' : 'close';
    emit(name, this);
  }
}

function handleClose() {
  if (!props.prevent) {
    open.value = false;
  }
}
</script>

<template>
  <div class="relative">
    <div @click="handleClick">
      <slot name="trigger" />
    </div>

    <!-- Full Screen Dropdown Overlay -->
    <div v-show="open" class="fixed inset-0 z-40" @click="handleClose" />

    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-show="open"
        class="absolute z-50 mt-2 rounded-md shadow-lg"
        :class="[widthClass, alignmentClasses]"
        style="display: none"
        @click="handleClose"
      >
        <div
          class="rounded-md ring-1 ring-primary/5"
          :class="contentClasses"
        >
          <slot name="content" />
        </div>
      </div>
    </transition>
  </div>
</template>
