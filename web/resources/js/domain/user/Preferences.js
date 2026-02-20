// import { request } from '../../utils/http/BackendRequest.js'
import { router } from '@inertiajs/vue3';

export const Preferences = {};

Preferences.updateSorter = async () => {
  // TODO implement project sorting preferences
};

Preferences.updateZoom = async ({ projectId, sourceId, level }) => {
  const data = {
    zoom: {
      source: {
        [sourceId]: level,
      },
    },
  };
  return router.put(endpoint(projectId), data, routeOptions);
};

Preferences.updateTheme = async (options) => {
  return router.put(endpoint(), options, routeOptions);
};

const endpoint = (project) =>
  project
    ? route('preferences.update.project', { project })
    : route('preferences.update.global');

const routeOptions = { preserveScroll: true, preserveState: true };
