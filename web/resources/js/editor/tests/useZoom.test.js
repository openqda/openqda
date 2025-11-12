import { describe, it, expect } from 'vitest';
import { useZoom } from '../useZoom.js'

describe('useZoom', () => {
    it('provides a default zoom of 1.0', () => {
        const { zoom } = useZoom();
        expect(zoom.value).toBe(1.0);
    })

    it('increases zoom level only to valid levels', () => {
        expect.fail('Not implemented yet');
    })
    it('not increases zoom level when limit is reached', () => {
        expect.fail('Not implemented yet');
    })
    it('decreases zoom level only to valid levels', () => {
        expect.fail('Not implemented yet');
    })
    it('not decreases zoom level when limit is reached', () => {
        expect.fail('Not implemented yet');
    })
});
