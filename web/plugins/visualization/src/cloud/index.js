export default {
  key: 'cloud',

  /**
   * Component name
   */
  name: 'WordCloudView',

  /**
   * Human-readable title
   */
  title: 'Word Cloud of Selections',
  /**
   * For filtering
   */
  type: 'visualization',

  /**
   * load Vue component
   */
  load: () => import('./WordCloudView.vue')
};
