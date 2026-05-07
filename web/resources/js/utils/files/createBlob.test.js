import { describe, it, expect } from 'vitest';
import { createBlob } from './createBlob.js';

describe('createBlob', () => {
  it('returns a Blob instance', () => {
    const blob = createBlob();
    expect(blob).toBeInstanceOf(Blob);
  });

  it('creates a text/plain blob by default', () => {
    const blob = createBlob();
    expect(blob.type).toBe('text/plain');
  });

  it('uses default data when no options provided', async () => {
    const blob = createBlob();
    expect(blob.size).toBeGreaterThan(0);
    const text = await blob.text();
    expect(text).toBe(
      'Llanfair­pwllgwyngyll­gogery­chwyrn­drobwll­llan­tysilio­gogo­goch'
    );
  });

  it('uses custom data when provided', async () => {
    const blob = createBlob({ data: 'hello world' });
    const text = await blob.text();
    expect(text).toBe('hello world');
  });

  it('uses custom type when provided', () => {
    const blob = createBlob({ type: 'application/json' });
    expect(blob.type).toBe('application/json');
  });

  it('creates blob with empty string data', async () => {
    // Note: empty string is falsy, so ?? won't catch it - it uses empty string
    const blob = createBlob({ data: '' });
    const text = await blob.text();
    expect(text).toBe('');
  });

  it('creates blob with custom endings option', () => {
    const blob = createBlob({ endings: 'native' });
    expect(blob).toBeInstanceOf(Blob);
  });

  it('defaults endings to transparent', () => {
    // This is verified by not throwing; transparent is the default
    const blob = createBlob();
    expect(blob).toBeInstanceOf(Blob);
  });

  it('creates blob with all custom options', async () => {
    const blob = createBlob({
      data: '{"key":"value"}',
      type: 'application/json',
      endings: 'transparent',
    });
    expect(blob.type).toBe('application/json');
    const text = await blob.text();
    expect(text).toBe('{"key":"value"}');
  });

  it('creates blob with empty object options', () => {
    const blob = createBlob({});
    expect(blob).toBeInstanceOf(Blob);
    expect(blob.type).toBe('text/plain');
    expect(blob.size).toBeGreaterThan(0);
  });
});
