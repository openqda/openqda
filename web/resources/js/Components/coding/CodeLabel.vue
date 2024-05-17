<template>
  <span
    :id="'codelabel' + props.label"
    class="code-label flex-grow break-all"
    @dblclick="onDblClick"
    :contenteditable="editMode"
    @click.stop
    @keydown="onKeyDown"
    @focus="setRange($event.target)"
    @blur="abortEditing($event.target)"
    @input="onInput($event.target)"
    >{{ display }}</span
  >
</template>

<script setup>
import { inject, nextTick, onMounted, ref, watch } from 'vue';

const editMode = ref(false);
const emit = defineEmits(['startedit', 'endedit', 'changed']);
const prev = ref('');
const display = ref('');
const current = ref('');
const props = defineProps({
  label: String,
  editable: Boolean,
});
inject('handleCodeItemsClickOutside');

/**
 * Resets the displayed label to the prev value
 * and ends the editing
 * @param target
 */
function resetToPreviousValue() {
  if (current.value.trim() === '') {
    current.value = prev.value;
    display.value = prev.value;
    const elementId = 'codelabel' + props.label;
    const codeLabelEl = document.getElementById(elementId);
    if (codeLabelEl) {
      codeLabelEl.textContent = prev.value; // Update DOM
    }
  }
}

/**
 * Aborts the editing and resets the label to the previous value
 * @param target
 */
function abortEditing(target) {
  let node = target.firstChild;
  if (!node) {
    node = document.createTextNode('');
    target.appendChild(node);
  }
  return endEditing(); // This will handle resetting to previous value
}

/**
 * Ends the editing and emits the 'endedit' event
 */
function endEditing() {
  resetToPreviousValue(); // Resetting to previous value happens here
  editMode.value = false;
  emit('endedit', { current: current.value, prev: prev.value });
}

const startEditing = () => {
  editMode.value = true;
  emit('startedit');
};

watch(
  () => props.label,
  (label) => {
    if (label.length === 0) {
      label = prev.value;

      return;
    }
    prev.value = label;
    current.value = label;
    display.value = label;
  },
  { immediate: true }
);

/**
 * Force-disable editing from outside
 */
watch(
  () => props.editable,
  (editable) => {
    if (!editable && editMode.value) {
      return endEditing();
    }
  }
);

async function onDblClick(event) {
  startEditing();
  await nextTick();
  setRange(event.target);
}

onMounted(async () => {
  if (props.editable) {
    startEditing();
    await nextTick();
    let span = document.getElementById('codelabel' + props.label);
    setRange(span);
  }
});

function onKeyDown({ target, key }) {
  if (key.toLowerCase() === 'escape') {
    return abortEditing(target);
  }
  if (key.toLowerCase() === 'enter') {
    if (current.value) {
      emit('changed', { current: current.value, prev: prev.value });
      return endEditing();
    } else {
      return false;
    }
  }
}

function setRange(target) {
  let range = document.createRange();
  let sel = window.getSelection();
  const node = target.firstChild;

  if (!node) {
    return;
  }
  const end = node.length;
  range.setStart(node, end);
  range.collapse(true);
  sel.removeAllRanges();
  sel.addRange(range);
}

function onInput(target) {
  current.value = target.firstChild?.textContent ?? '';
}
</script>

<style scoped>
[contenteditable]:focus {
  outline: none !important; /* Removes the browser default outline */
  border: 0 solid;
  cursor: text !important;
}

.code-label:hover {
  cursor: pointer;
}
</style>
