import { describe, it, expect, beforeEach } from 'vitest';
import { useZoom } from '../useZoom.js';

describe('useZoom', () => {
  let zoom, setZoom;
  beforeEach(() => {
    const result = useZoom();
    zoom = result.zoom;
    setZoom = result.setZoom;
    setZoom(1.0); // zoom starts at 1.0 for every test
  });

  it('provides a default zoom of 1.0', () => {
    expect(zoom.value).toBe(1.0);
  });

  it('increases zoom level only to valid levels', () => {
    setZoom('increase'); // 1.0 -> 1.15
    expect(zoom.value).toBe(1.15);
    setZoom('increase'); // 1.15 -> 1.3
    expect(zoom.value).toBe(1.3);
    setZoom(0.85); // direct set
    setZoom('increase'); // 0.85 -> 1.0
    expect(zoom.value).toBe(1.0);
  });

  it('not increases zoom level when limit is reached', () => {
    setZoom(1.5);
    setZoom('increase'); // should stay at 1.5
    expect(zoom.value).toBe(1.5);
  });

  it('decreases zoom level only to valid levels', () => {
    // Start from a known state
    setZoom(1.0);
    setZoom('decrease'); // 1.0 -> 0.85
    expect(zoom.value).toBe(0.85);
    setZoom('decrease'); // 0.85 -> 0.7
    expect(zoom.value).toBe(0.7);

    setZoom(1.3);
    setZoom('decrease'); // 1.3 -> 1.15
    expect(zoom.value).toBe(1.15);
  });

  it('not decreases zoom level when limit is reached', () => {
    setZoom(0.7);
    setZoom('decrease'); // should remain 0.7
    expect(zoom.value).toBe(0.7);
  });

  it('accepts direct numeric zoom values', () => {
    setZoom(1.3);
    expect(zoom.value).toBe(1.3);
  });
});

