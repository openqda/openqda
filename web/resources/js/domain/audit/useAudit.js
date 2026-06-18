import { reactive, toRefs } from 'vue';
import { request } from '../../utils/http/BackendRequest.js';

const state = reactive({
  audits: {
    data: [],
    current_page: 1,
    last_page: 1,
  },
  auditCounts: {},
  forProjectId: null,
});

/**
 * Manage audits for a project using a vue composable for
 * reactive update beyond a single template.
 */
export const useAudit = () => {
  const { audits, auditCounts, forProjectId } = toRefs(state);
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
      const auditsData = (
        Array.isArray(response.data.audits.data)
          ? response.data.audits.data
          : Object.values(response.data.audits.data || {})
      ).toSorted((a, b) => {
        return b.created_at_timestamp - a.created_at_timestamp;
      });
      state.audits = {
        ...response.data.audits,
        data: auditsData,
      };
      state.auditCounts = response.data.audit_counts;
      state.forProjectId = projectId;
    } else {
      state.audits = { data: [], current_page: 1, last_page: 1 };
    }

    return { success, response, error };
  };

  /**
   * Load all audits for a project as a flat list (no filters, no pagination).
   * Used for CSV export.
   */
  const loadAllAudits = async ({ projectId }) => {
    const url = route('project.audit-export', { project: projectId });
    const { response, error } = await request({ url, type: 'get' });
    const success = response?.data?.success;
    const allAudits = success
      ? Array.isArray(response.data.audits)
        ? response.data.audits
        : Object.values(response.data.audits || {})
      : [];
    return { success, audits: allAudits, error };
  };

  return {
    audits,
    auditCounts,
    loadAudits,
    loadAllAudits,
    forProjectId,
  };
};
