import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { vClickOutside } from './clickOutsideDirective.js';

describe('vClickOutside', () => {
  let el;
  let binding;

  beforeEach(() => {
    el = document.createElement('div');
    document.body.appendChild(el);
    binding = {
      value: {
        callback: vi.fn(),
      },
    };
  });

  afterEach(() => {
    // Clean up: unmount and remove element
    if (el.handleOutsideClick) {
      vClickOutside.unmounted(el);
    }
    el.remove();
    vi.restoreAllMocks();
  });

  it('has mounted and unmounted hooks', () => {
    expect(vClickOutside.mounted).toBeTypeOf('function');
    expect(vClickOutside.unmounted).toBeTypeOf('function');
  });

  it('registers a click event listener on document when mounted', () => {
    const spy = vi.spyOn(document, 'addEventListener');
    vClickOutside.mounted(el, binding);
    expect(spy).toHaveBeenCalledWith('click', el.handleOutsideClick);
  });

  it('stores handleOutsideClick on the element', () => {
    vClickOutside.mounted(el, binding);
    expect(el.handleOutsideClick).toBeTypeOf('function');
  });

  it('calls callback when clicking outside the element', () => {
    vClickOutside.mounted(el, binding);
    const outsideEl = document.createElement('span');
    document.body.appendChild(outsideEl);

    const event = new MouseEvent('click', { bubbles: true });
    outsideEl.dispatchEvent(event);

    expect(binding.value.callback).toHaveBeenCalledWith(event, el);
    outsideEl.remove();
  });

  it('does not call callback when clicking inside the element', () => {
    vClickOutside.mounted(el, binding);
    const child = document.createElement('span');
    el.appendChild(child);

    const event = new MouseEvent('click', { bubbles: true });
    child.dispatchEvent(event);

    expect(binding.value.callback).not.toHaveBeenCalled();
  });

  it('does not call callback when clicking the element itself', () => {
    vClickOutside.mounted(el, binding);

    const event = new MouseEvent('click', { bubbles: true });
    el.dispatchEvent(event);

    expect(binding.value.callback).not.toHaveBeenCalled();
  });

  it('removes the event listener when unmounted', () => {
    vClickOutside.mounted(el, binding);
    const handler = el.handleOutsideClick;
    const spy = vi.spyOn(document, 'removeEventListener');
    vClickOutside.unmounted(el);
    expect(spy).toHaveBeenCalledWith('click', handler);
  });

  it('does not call callback after unmounting', () => {
    vClickOutside.mounted(el, binding);
    vClickOutside.unmounted(el);

    const outsideEl = document.createElement('span');
    document.body.appendChild(outsideEl);

    const event = new MouseEvent('click', { bubbles: true });
    outsideEl.dispatchEvent(event);

    expect(binding.value.callback).not.toHaveBeenCalled();
    outsideEl.remove();
  });
});
