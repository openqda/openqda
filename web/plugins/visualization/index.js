import List from './src/list';
import Portrait from './src/portrait';
import WordCloud from './src/cloud';
import GroupedBarChart from './src/groupedBar';

/**
 * passes all default visualization plugins
 * to the given plugin api.
 */
export default function register(api) {
  [List, Portrait, WordCloud, GroupedBarChart].forEach((plugin) =>
    api.register(plugin)
  );
}
