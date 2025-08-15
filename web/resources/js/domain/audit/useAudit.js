import { reactive, toRefs } from 'vue';
import { request } from '../../utils/http/BackendRequest.js';

const state = reactive({
  audits: {
    data: [],
    current_page: 1,
    last_page: 1,
  },
  forProjectId: null,
});

/**
 * Manage audits for a project using a vue composable for
 * reactive update beyond a single template.
 */
export const useAudit = () => {
  const { audits, forProjectId } = toRefs(state);
  const loadAudits = async ({ projectId, page, filters }) => {
    const params = new URLSearchParams();
    params.append('page', page);

    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== '') {
        if (Array.isArray(value)) {
          value.forEach((v) => params.append(`${key}[]`, v));
        } else {
          params.append(key, value);
        }
      }
    });

    const endpoint = `/audits/${projectId}?${params.toString()}`;
    const { response, error } = await request({
      url: endpoint,
      type: 'get',
    });
    const success = response?.data?.success;
    if (!error && success) {
      state.audits = {
        ...response.data.audits,
        data: Array.isArray(response.data.audits.data)
          ? response.data.audits.data
          : Object.values(response.data.audits.data || {}),
      };
      state.forProjectId = projectId;
    } else {
      state.audits = { data: [], current_page: 1, last_page: 1 };
    }

    return { success, response, error };
  };
  return {
    audits,
    loadAudits,
    forProjectId,
  };
};
