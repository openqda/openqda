import { reactive, readonly, ref, toRef, toRefs } from 'vue';
import { debounce } from '../../../utils/dom/debounce.js';

export const createVisualizationAPI = ({
  sources,
  checkedSources,
  codes,
  checkedCodes,
}) => {
  const optionsSchema = ref({});

  const eachCheckedSources = (callback) => {
    for (const source of sources.value) {
      if (checkedSources.value.get(source.id)) {
        callback();
      }
    }
  };

  const eachCheckedCodes = (callback) => {
    for (const code of codes.value) {
      if (checkedCodes.value.get(code.id)) {
        callback(code);
      }
    }
  };

  const getAllSelections = () => {
    const out = [];
    eachCheckedSources((source) => {
      eachCheckedCodes((code) => {
        for (const selection of code.text) {
          if (source.id === selection.source_id) {
            out.push(selection);
          }
        }
      });
    });
    return out;
  };
  const defineOptions = (schema) => {
    optionsSchema.value = schema;
  };

  const getCodesForSource = (source) => {
    return codes.value.filter(
      (code) =>
        !!checkedCodes.value.get(code.id) &&
        code.text.some((t) => t.source_id === source.id)
    );
  };

  return {
    api: {
      sources: readonly(sources),
      codes: readonly(codes),
      eachCheckedSources,
      eachCheckedCodes,
      getAllSelections,
      getCodesForSource,
      debounce,
      defineOptions,
    },
    optionsSchema,
  };
};
