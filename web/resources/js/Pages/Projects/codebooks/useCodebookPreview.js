import { reactive, toRefs } from 'vue';
import axios from 'axios';

const state = reactive({
  codebook: null,
  loading: false,
});

export const useCodebookPreview = () => {
  const { codebook, loading } = toRefs(state);

  const open = async ({ codebook }) => {
    state.loading = true;

    // If codebook doesn't have codes loaded, fetch them
    if (!codebook.codes) {
      try {
        const response = await axios.get(`/api/codebooks/${codebook.id}/codes`);
        state.codebook = response.data;
        if (state.codebook.codes?.length) {
          const original = [...state.codebook.codes];
          try {
            state.codebook.codes = toSorted(state.codebook.codes);
          } catch (e) {
            console.warn('Failed to sort codes:', e);
            state.codebook.codes = original;
          }
        }
      } catch (error) {
        console.error('Failed to fetch codebook codes:', error);
        // Fallback to the original codebook if fetching fails
        state.codebook = codebook;
      }
    } else {
      state.codebook = codebook;
    }

    state.loading = false;
  };

  const close = () => {
    state.codebook = null;
    state.loading = false;
  };

  return {
    codebook,
    loading,
    open,
    close,
  };
};

const toSorted = (codes, maxDepth = 50) => {
  const out = [];
  codes.sort((a, b) => a.name.localeCompare(b.name));
  const getCodes = (parentId, depth = 0) => {
    if (depth > maxDepth) {
      throw new Error('Max depth exceeded');
    }
    codes
      .filter((c) => c.parent_id === parentId)
      .forEach((code) => {
        code.depth = depth;
        out.push(code);
        getCodes(code.id, depth + 1);
      });
  };
  getCodes(null);
  return out;
};
