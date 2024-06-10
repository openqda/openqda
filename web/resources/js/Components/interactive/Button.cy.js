import Button from './Button.vue';

describe('<Button />', () => {
  it('renders a default button', () => {
    // see: https://on.cypress.io/mounting-vue
    cy.mount(Button, { props: { color: 'red', label: 'click' } });
  });
});
