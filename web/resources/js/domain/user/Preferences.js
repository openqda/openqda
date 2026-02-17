// import { request } from '../../utils/http/BackendRequest.js'
import { router } from '@inertiajs/vue3';

export const Preferences = {};

Preferences.updateSorter = async () => {
  // TODO implement project sorting preferences
};

Preferences.updateZoom = async ({ projectId, sourceId, level }) => {
  router.put(
    route('projects.preferences.update', { project: projectId }),
    {
      zoom: {
        source: {
          [sourceId]: level,
        },
      },
    },
    {
      preserveScroll: true,
      preserveState: true,
    }
  );
};
