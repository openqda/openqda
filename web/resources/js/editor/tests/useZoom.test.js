import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { useZoom } from '../useZoom.js';
import { Preferences } from '../../domain/user/Preferences.js';
import sinon from 'sinon';

//Mock test environment for useZoom composable
vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({
    props: {
      projectId: 1,
      preferences: [
        {
          zoom: {
            source: {
              sourceA: 1.0,
            },
          },
        },
      ],
    },
  }),
}));

describe('useZoom', () => {
  let setZoom, getZoom, preferenceCall;

  beforeEach(() => {
    const result = useZoom();
    getZoom = result.getZoom;
    setZoom = result.setZoom;
    preferenceCall = sinon.stub(Preferences, 'updateZoom').resolves();
  });

  afterEach(() => {
    preferenceCall.restore();
  });

  it('provides a default zoom of 1.0', () => {
    expect(getZoom('unknownSource')).toBe(1.0);
  });

  it('uses backend zoom if available', () => {
    expect(getZoom('sourceA')).toBe(1.0);
  });

  it('increases zoom level only to valid levels', async () => {
    await setZoom('increase', 'sourceA'); // 1.0 -> 1.15
    expect(getZoom('sourceA')).toBe(1.15);
    await setZoom('increase', 'sourceA'); // 1.15 -> 1.3
    expect(getZoom('sourceA')).toBe(1.3);
    await setZoom(0.85, 'sourceA'); // direct set
    await setZoom('increase', 'sourceA'); // 0.85 -> 1.0
    expect(getZoom('sourceA')).toBe(1.0);
  });

  it('not increases zoom level when limit is reached', async () => {
    await setZoom(2.0, 'sourceA');
    expect(getZoom('sourceA')).toBe(2.0);

    await setZoom('increase', 'sourceA'); // should stay 2.0
    expect(getZoom('sourceA')).toBe(2.0);

    // updateZoom called twice: once for setting 2.0, once for "increase" attempt.
    expect(preferenceCall.callCount).toBe(2);
  });

  it('decreases zoom level only to valid levels', async () => {
    // Start from a known state
    await setZoom(1.0, 'sourceA');
    await setZoom('decrease', 'sourceA'); // 1.0 -> 0.85
    expect(getZoom('sourceA')).toBe(0.85);
    await setZoom('decrease', 'sourceA'); // 0.85 -> 0.7
    expect(getZoom('sourceA')).toBe(0.7);

    await setZoom(1.3, 'sourceA');
    await setZoom('decrease', 'sourceA'); // 1.3 -> 1.15
    expect(getZoom('sourceA')).toBe(1.15);
  });

  it('not decreases zoom level when limit is reached', async () => {
    await setZoom(0.3, 'sourceA');
    expect(getZoom('sourceA')).toBe(0.3);
    await setZoom('decrease', 'sourceA'); // should remain 0.3
    expect(getZoom('sourceA')).toBe(0.3);
    // Only 1 call: Already at lowest, so no updateZoom call for decrease
    expect(preferenceCall.callCount).toBe(1);
  });

  it('accepts direct numeric zoom values', async () => {
    await setZoom(1.75, 'sourceA');
    expect(getZoom('sourceA')).toBe(1.75);
    sinon.assert.calledWith(preferenceCall, {
      projectId: 1,
      sourceId: 'sourceA',
      level: 1.75,
    });
  });
});
