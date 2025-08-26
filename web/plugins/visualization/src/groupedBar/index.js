export default {
  key: 'groupedBar',

  /**
   * Component name
   */
  name: 'CodeCountBySource',

  /**
   * Human-readable title
   */
  title: 'Code Selection Count by Source',
  /**
   * For filtering
   */
  type: 'visualization',

  /**
   * load Vue component
   */
  load: () => import('./GroupedBarChart.vue'),
};
