import { reactive, toRefs } from 'vue'

// ─────────────────────────────────────────────────────────────
// VIEW ZOOM (per user) - numeric zoom levels
// Resets to default on page refresh
// ─────────────────────────────────────────────────────────────
const ZOOM_LEVELS = [0.7, 0.85, 1.0, 1.15, 1.3, 1.5]; // Available zoom levels
const DEFAULT_ZOOM = 1.0;

const state = reactive({
    zoom: DEFAULT_ZOOM // Always start with default zoom on page load
})

export const useZoom = () => {
    const { zoom } = toRefs(state);

    function setZoom(action) {
        let newZoom = zoom.value;
        // Only handle increase/decrease/reset - not size names
        if (action === 'increase') {
            // Find next higher zoom level
            const currentIndex = ZOOM_LEVELS.findIndex((z) => z >= zoom.value);
            if (currentIndex < ZOOM_LEVELS.length - 1) {
                newZoom = ZOOM_LEVELS[currentIndex + 1];
            }
        } else if (action === 'decrease') {
            // Find next lower zoom level
            const currentIndex = ZOOM_LEVELS.findIndex((z) => z >= zoom.value);
            if (currentIndex > 0) {
                newZoom = ZOOM_LEVELS[currentIndex - 1];
            } else if (currentIndex === 0) {
                // Already at lowest, stay there
                return;
            }
        } else if (action === 'reset') {
            newZoom = DEFAULT_ZOOM;
        } else if (typeof action === 'number') {
            // Direct zoom value
            newZoom = action;
        }

        // Update zoom without saving to localStorage (resets on refresh)
        state.zoom = newZoom;
    }

    return { zoom, setZoom };
}
