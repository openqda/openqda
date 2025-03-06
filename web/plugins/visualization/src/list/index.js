export default {
  key: 'list',

  /**
   * Component name
   */
  name: 'ListView',

  /**
   * Human-readable title
   */
  title: 'List of Selections',
  /**
   * For filtering
   */
  type: 'visualization',

  /**
   * load Vue component
   */
  load: () => import('./ListView.vue'),
};
