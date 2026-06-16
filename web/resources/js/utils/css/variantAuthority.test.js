import { variantAuthority } from './variantAuthority.js';

describe(variantAuthority.name, () => {
  it('returns a function that returns the default classes if no variants are provided', () => {
    const resolver = variantAuthority({ class: 'default-class' });
    expect(resolver()).toBe('default-class');
  });
  it('returns a function that resolves variants correctly', () => {
    const resolver = variantAuthority({
      class: 'default-class',
      variants: {
        color: {
          red: 'text-red-500',
          blue: 'text-blue-500',
        },
        size: {
          small: 'text-sm',
          large: 'text-lg',
        },
      },
      defaultVariants: {
        color: 'red',
        size: 'small',
      },
    });
    expect(resolver()).toBe('default-class text-red-500 text-sm');
    expect(resolver({ color: 'blue' })).toBe(
      'default-class text-blue-500 text-sm'
    );
    expect(resolver({ size: 'large' })).toBe(
      'default-class text-red-500 text-lg'
    );
    expect(resolver({ color: 'blue', size: 'large' })).toBe(
      'default-class text-blue-500 text-lg'
    );
  });
});
