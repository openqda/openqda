import { cn } from './cn.js';

describe(cn.name, () => {
  it('returns empty string if no arguments are passed', () => {
    expect(cn()).toBe('');
  });
  it('returns the class name if a string is passed', () => {
    expect(cn('class1')).toBe('class1');
  });
  it('returns the class names separated by space if multiple strings are passed', () => {
    expect(cn('class1', 'class2')).toBe('class1 class2');
  });
  it('ignores falsy values', () => {
    expect(cn('class1', false, 'class2', null, undefined, '', 'class3')).toBe(
      'class1 class2 class3'
    );
  });
  it('removes duplicate class names', () => {
    expect(cn('class1', 'class2', 'class1', 'class3', 'class2')).toBe(
      'class1 class2 class3'
    );
  });
  it('should merge Tailwind classes correctly', () => {
    expect(cn('p-2', 'p-4', 'text-center', 'text-left')).toBe('p-4 text-left');
  });
});
