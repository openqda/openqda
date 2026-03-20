import { reactive, toRefs } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Preferences } from '../domain/user/Preferences.js';

// ─────────────────────────────────────────────────────────────
// VIEW ZOOM (per user) - numeric zoom levels
// Resets to default on page refresh
// ─────────────────────────────────────────────────────────────
const ZOOM_LEVELS = [0.3, 0.5, 0.7, 0.85, 1.0, 1.15, 1.3, 1.5, 1.75, 2.0]; // Available zoom levels
const DEFAULT_ZOOM = 1.0;

const state = reactive({
  zoom: DEFAULT_ZOOM, // Always start with default zoom on page load
  sources: {},
});

export const useZoom = () => {
  const { projectId, preferences } = usePage().props;
  const { sources } = toRefs(state);
  const prefs = preferences?.[0] ?? {};

  const getZoom = (sourceId) => {
    // prefer zoom levels set during this session (not saved to backend, resets on refresh)
    if (sources.value[sourceId] !== undefined) {
      return sources.value[sourceId];
    }
    // fall back to preferences from backend
    return prefs.zoom?.source?.[sourceId] ?? DEFAULT_ZOOM;
  };

  const setZoom = async (action, sourceId) => {
    const oldZoom = getZoom(sourceId);
    let newZoom = getZoom(sourceId);
    // Only handle increase/decrease - not size names
    if (action === 'increase') {
      // Find next higher zoom level
      const currentIndex = ZOOM_LEVELS.findIndex((z) => z >= oldZoom);
      if (currentIndex < ZOOM_LEVELS.length - 1) {
        newZoom = ZOOM_LEVELS[currentIndex + 1];
      }
    } else if (action === 'decrease') {
      // Find next lower zoom level
      const currentIndex = ZOOM_LEVELS.findIndex((z) => z >= oldZoom);
      if (currentIndex > 0) {
        newZoom = ZOOM_LEVELS[currentIndex - 1];
      } else if (currentIndex === 0) {
        // Already at lowest, stay there
        return;
      }
    } else if (typeof action === 'number') {
      // Direct zoom value
      newZoom = action;
    }

    // Update backend preferences
    await Preferences.updateZoom({ projectId, sourceId, level: newZoom });

    // Update zoom without saving to localStorage (resets on refresh)
    state.sources[sourceId] = newZoom;
  };

  return { getZoom, setZoom };
};
