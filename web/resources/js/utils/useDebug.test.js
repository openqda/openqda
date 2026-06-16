import { describe, it, expect, vi, afterEach } from 'vitest';

// We need to mock import.meta.env before importing useDebug
// Since vitest globals are enabled and env is set up by vite, we
// use vi.stubEnv for environment variables

describe('useDebug', () => {
  afterEach(() => {
    vi.unstubAllEnvs();
    vi.restoreAllMocks();
  });

  it('returns a noop function when no debug mode is configured', async () => {
    vi.stubEnv('VITE_DEBUG_CLIENT', '');
    vi.stubEnv('DEBUG_CLIENT', '');
    // Re-import to get fresh module
    const { useDebug } = await import('./useDebug.js');
    const debug = useDebug();
    // noop returns undefined
    expect(debug()).toBeUndefined();
  });

  it('returns a function when debug mode is explicitly provided', async () => {
    const { useDebug } = await import('./useDebug.js');
    const debug = useDebug({ mode: 'log' });
    expect(debug).toBeTypeOf('function');
  });

  it('prepends scope to debug output when scope is provided', async () => {
    const { useDebug } = await import('./useDebug.js');
    const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
    const debug = useDebug({ mode: 'warn', scope: 'TestComponent' });
    debug('test message');
    expect(consoleSpy).toHaveBeenCalledWith(
      '[warn][TestComponent]:',
      'test message'
    );
  });

  it('prepends only mode prefix when no scope is provided', async () => {
    const { useDebug } = await import('./useDebug.js');
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    const debug = useDebug({ mode: 'error' });
    debug('error message');
    expect(consoleSpy).toHaveBeenCalledWith('[error]:', 'error message');
  });

  it('supports log mode', async () => {
    const { useDebug } = await import('./useDebug.js');
    const consoleSpy = vi.spyOn(console, 'log').mockImplementation(() => {});
    const debug = useDebug({ mode: 'log' });
    debug('hello');
    expect(consoleSpy).toHaveBeenCalled();
  });

  it('supports info mode', async () => {
    const { useDebug } = await import('./useDebug.js');
    const consoleSpy = vi.spyOn(console, 'info').mockImplementation(() => {});
    const debug = useDebug({ mode: 'info' });
    debug('hello');
    expect(consoleSpy).toHaveBeenCalled();
  });

  it('supports debug mode', async () => {
    const { useDebug } = await import('./useDebug.js');
    const consoleSpy = vi.spyOn(console, 'debug').mockImplementation(() => {});
    const debug = useDebug({ mode: 'debug' });
    debug('hello');
    expect(consoleSpy).toHaveBeenCalled();
  });

  it('passes multiple arguments to the console method', async () => {
    const { useDebug } = await import('./useDebug.js');
    const consoleSpy = vi.spyOn(console, 'log').mockImplementation(() => {});
    const debug = useDebug({ mode: 'log' });
    debug('a', 'b', 'c');
    expect(consoleSpy).toHaveBeenCalledWith('[log]:', 'a', 'b', 'c');
  });

  it('returns noop when given an invalid console method', async () => {
    const { useDebug } = await import('./useDebug.js');
    const debug = useDebug({ mode: 'nonexistent_method' });
    // Should not throw
    expect(debug('test')).toBeUndefined();
  });

  it('exposes useDebug on window', async () => {
    await import('./useDebug.js');
    expect(window.useDebug).toBeTypeOf('function');
  });
});
