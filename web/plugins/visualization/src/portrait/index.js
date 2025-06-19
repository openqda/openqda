export default {
  key: 'portrait',

  /**
   * Component name
   */
  name: 'CodePortrait',

  /**
   * Human-readable title
   */
  title: 'Code Portrait',

  /**
   * For filtering
   */
  type: 'visualization',

  /**
   * load Vue component
   */
  load: () => import('./CodePortrait.vue'),
};
