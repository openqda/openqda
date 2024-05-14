import { onBeforeUnmount, onMounted } from 'vue'

export function useOutsideClickListener(elements, callback) {
  const handleOutsideClick = (event) => {
    if (!elements || !elements.value) {
      return
    }

    let isOutside = false

    if (Array.isArray(elements.value)) {
      // Check if outside click for an array of elements
      isOutside = !Array.from(elements.value).some(
        (item) => item === event.target || item.contains(event.target)
      )
    } else {
      // Check if outside click for a single element
      isOutside = !(
        elements.value === event.target || elements.value.contains(event.target)
      )
    }

    if (isOutside) {
      if (typeof callback === 'function') {
        callback()
      }
    }
  }

  onMounted(() => {
    window.addEventListener('click', handleOutsideClick)
  })

  onBeforeUnmount(() => {
    window.removeEventListener('click', handleOutsideClick)
  })
}
