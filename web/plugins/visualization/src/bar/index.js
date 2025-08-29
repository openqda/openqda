export default {
  key: 'groupedBar',

  /**
   * Component name
   */
  name: 'CodeCountBySource',

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

  /**
   * let host render options button
   */
  hasOptions: true,
};
