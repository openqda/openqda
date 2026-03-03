// import { request } from '../../utils/http/BackendRequest.js'
import { router } from '@inertiajs/vue3';

export const Preferences = {};

//update project sorting
Preferences.updateSorter = async (options) => {
  return router.put(endpoint(), options, routeOptions);
};

//update source sorting
Preferences.updateSourceSorter = async ({ projectId, sortRules }) => {
  const data = {
    sources: {
      sort: sortRules,
    },
  };

  return router.put(endpoint(projectId), data, routeOptions);
};

//update zoom level
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

//update codebooks visibility
Preferences.updateCodeVisibility = async ({
  projectId,
  codebookId,
  codeId,
  visible,
}) => {
  const data = {
    codebooks: {
      [codebookId]: {
        visibility: {
          [codeId]: visible,
        },
      },
    },
  };

  return router.put(endpoint(projectId), data, routeOptions);
};

//Update analysis visibility
Preferences.updateAnalysisVisibility = async ({
  projectId,
  type,
  id,
  value,
}) => {
  const data = {
    analysis: {
      visibility: {
        [type]: {
          [id]: value,
        },
      },
    },
  };

  return router.put(endpoint(projectId), data, routeOptions);
};

//update theme preference
Preferences.updateTheme = async (options) => {
  return router.put(endpoint(), options, routeOptions);
};

const endpoint = (project) =>
  project
    ? route('preferences.update.project', { project })
    : route('preferences.update.global');

const routeOptions = { preserveScroll: true, preserveState: true };
