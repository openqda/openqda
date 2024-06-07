import { render } from '@testing-library/vue';
import Headline2 from './Headline2.vue';

test('it should work', () => {
  const { getByText } = render(Headline2, {
    slots: {
      default: 'Foobar',
    },
  });

  // assert output
  getByText('Foobar');
});
