import { reactive, readonly, toRefs } from 'vue';
import { debounce } from '../../../utils/dom/debounce.js';
import { cn } from '../../../utils/css/cn.js';
import { useResizeObserver } from '../resizeObserver.js';

/**
 * @module
 */

/**
 * @private
 */
const state = reactive({
  schema: null,
  optionsFormData: null,
});

/**
 * Creates a new visualization API that is injected into
 * the current loaded Visualization plugin.
 * @param sources {object[]}
 * @param checkedSources {Map}
 * @param codes {object[]}
 * @param checkedCodes {Map}
 * @return {{optionsSchema: ToRef<(UnwrapNestedRefs<{schema: null, optionsFormData: null}> & {})["schema"]>, api: {codes: DeepReadonly<UnwrapNestedRefs<object>>, debounce: ((function(Function, number, boolean=): ((function(): void)|*))|*), eachCheckedCodes: eachCheckedCodes, eachCheckedSources: eachCheckedSources, useResizeObserver: ((function(): {resizeRef: React.Ref<*>, resizeState: Reactive<{dimensions: {}}>})|*), defineOptions: ((function(*): never)|*), sources: DeepReadonly<UnwrapNestedRefs<object>>, getCodesForSource: (function(*): *), cn: ((function(...[*]): string)|*), getAllSelections: (function(): *[])}}}
 */
export const createVisualizationAPI = ({
  sources,
  checkedSources,
  codes,
  checkedCodes,
}) => {
  const { schema: optionsSchema } = toRefs(state);

  /**
   * Allows to iterate all sources that are currently "checked"
   * for being included in the output dataset.
   * @param callback {function(Source):void}
   */
  const eachCheckedSources = (callback) => {
    for (const source of sources.value) {
      if (checkedSources.value.get(source.id)) {
        const doc = sources.value.find((s) => s.id === source.id);
        callback(doc);
      }
    }
  };

  /**
   * Allows to iterate all codes that are currently "checked"
   * for being included in the output dataset.
   * @param callback {function(Code):void}
   */
  const eachCheckedCodes = (callback) => {
    for (const code of codes.value) {
      if (checkedCodes.value.get(code.id)) {
        callback(code);
      }
    }
  };
  /**
   * Returns a list with all selections that
   * are included in the output dataset.
   * For inclusion, its related code and source
   * must be included.
   * @return {Selection[]}
   */
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

  /**
   * Not yet implemented
   * @private
   * @param schema
   */
  const defineOptions = (/* schema */) => {
    throw new Error('Not yet implemented!');
    // TODO - before we can leverage inversion of control
    //  for form data we need to use v-models on every
    //  autoform element and test for regressions!
    // state.schema = schema;
    // return optionsFormData
  };

  /**
   * Returns a list with all codes that
   * are included (checked) and related to
   * the given source.
   * @return {Code[]}
   */
  const getCodesForSource = (source) => {
    return codes.value.filter(
      (code) =>
        !!checkedCodes.value.get(code.id) &&
        code.text.some((t) => t.source_id === source.id)
    );
  };

  return {
    api: {
      cn,
      sources: readonly(sources),
      codes: readonly(codes),
      eachCheckedSources,
      eachCheckedCodes,
      getAllSelections,
      getCodesForSource,
      useResizeObserver,
      debounce,
      defineOptions,
    },
    optionsSchema,
  };
};
