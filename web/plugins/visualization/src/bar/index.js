export default {
  key: 'bar',

  /**
   * Component name
   */
  name: 'CodeCount',

  /**
   * Human-readable title
   */
  title: 'Code Selection Count',
  /**
   * For filtering
   */
  type: 'visualization',

  /**
   * load Vue component
   */
  load: () => import('./BarChart.vue'),
};
