<template>
  <div
    :data-active="active"
    @dragenter.prevent="setActive"
    @dragover.prevent="setActive"
    @dragleave.prevent="setInactive"
    @drop.prevent="onDrop"
    :class="[active ? 'bg-secondary/10' : 'bg-transparent']"
  >
    <slot :dropZoneActive="active"></slot>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const emit = defineEmits(['files-dropped']);
const props = defineProps({
  accept: String,
});
let active = ref(false);
let inActiveTimeout = null;

// setActive and setInactive use timeouts, so that when you drag an item over a child element,
// the dragleave event that is fired won't cause a flicker. A few ms should be plenty of
// time to wait for the next dragenter event to clear the timeout and set it back to active.
function setActive() {
  active.value = true;
  clearTimeout(inActiveTimeout);
}

function setInactive() {
  inActiveTimeout = setTimeout(() => {
    active.value = false;
  }, 50);
}

function onDrop(e) {
  setInactive();
  const accept = props.accept && new Set(props.accept.split(','));
  const files = [...e.dataTransfer.files].filter((file) => {
    if (!props.accept) {
      return true;
    }
    const split = file.name.split('.');
    const ending = `.${split[split.length - 1]}`;
    return accept.has(ending);
  });
  emit('files-dropped', files);
}

function preventDefaults(e) {
  e.preventDefault();
}

const events = ['dragenter', 'dragover', 'dragleave', 'drop'];

onMounted(() => {
  events.forEach((eventName) => {
    document.body.addEventListener(eventName, preventDefaults);
  });
});

onUnmounted(() => {
  events.forEach((eventName) => {
    document.body.removeEventListener(eventName, preventDefaults);
  });
});
</script>
