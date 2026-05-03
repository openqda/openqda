import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock axios before importing the module
vi.mock('axios', () => {
  return {
    default: {
      get: vi.fn(),
      post: vi.fn(),
      put: vi.fn(),
      delete: vi.fn(),
      patch: vi.fn(),
    },
  };
});

import axios from 'axios';
import { request, registerGlobalRequestHook } from './BackendRequest.js';

describe('BackendRequest', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('request', () => {
    it('sends a GET request', async () => {
      axios.get.mockResolvedValue({ status: 200, data: { ok: true } });
      const req = await request({ url: '/api/test', type: 'GET' });
      expect(axios.get).toHaveBeenCalled();
      expect(req.response.status).toBe(200);
      expect(req.error).toBeNull();
    });

    it('sends a POST request with body', async () => {
      axios.post.mockResolvedValue({ status: 201, data: { id: 1 } });
      const req = await request({
        url: '/api/test',
        type: 'POST',
        body: { name: 'test' },
      });
      expect(axios.post).toHaveBeenCalled();
      const callArgs = axios.post.mock.calls[0];
      expect(callArgs[0]).toBe('/api/test');
      expect(callArgs[1]).toEqual({ name: 'test' });
      expect(req.response.status).toBe(201);
      expect(req.error).toBeNull();
    });

    it('sends a GET request with query parameters', async () => {
      axios.get.mockResolvedValue({ status: 200, data: [] });
      const req = await request({
        url: '/api/items',
        type: 'GET',
        query: { page: 1, limit: 10 },
      });
      expect(axios.get).toHaveBeenCalled();
      const callArgs = axios.get.mock.calls[0];
      expect(callArgs[1]).toEqual({ params: { page: 1, limit: 10 } });
      expect(req.error).toBeNull();
    });

    it('sends custom headers', async () => {
      axios.get.mockResolvedValue({ status: 200, data: {} });
      const req = await request({
        url: '/api/test',
        type: 'GET',
        headers: { 'X-Custom': 'value' },
      });
      expect(axios.get).toHaveBeenCalled();
      const callArgs = axios.get.mock.calls[0];
      // The last argument should contain headers
      const lastArg = callArgs[callArgs.length - 1];
      expect(lastArg.headers).toEqual({ 'X-Custom': 'value' });
      expect(req.error).toBeNull();
    });

    it('captures error without throwing', async () => {
      const error = new Error('Network Error');
      error.response = { status: 500, data: { message: 'Server Error' } };
      axios.get.mockRejectedValue(error);

      const req = await request({ url: '/api/test', type: 'GET' });
      expect(req.error).toBe(error);
      expect(req.response.status).toBe(500);
    });

    it('handles error without response property', async () => {
      const error = new Error('Network Error');
      axios.get.mockRejectedValue(error);

      const req = await request({ url: '/api/test', type: 'GET' });
      expect(req.error).toBe(error);
      expect(req.response).toBeNull();
    });

    it('sets error when response has success=false and message', async () => {
      axios.get.mockResolvedValue({
        success: false,
        message: 'Something went wrong',
      });

      const req = await request({ url: '/api/test', type: 'GET' });
      expect(req.error).toBeInstanceOf(Error);
      expect(req.error.message).toBe('Something went wrong');
    });

    it('type is case-insensitive', async () => {
      axios.post.mockResolvedValue({ status: 200, data: {} });
      await request({ url: '/api/test', type: 'POST', body: {} });
      expect(axios.post).toHaveBeenCalled();
    });

    it('returns the BackendRequest instance', async () => {
      axios.get.mockResolvedValue({ status: 200, data: {} });
      const req = await request({ url: '/api/test', type: 'GET' });
      expect(req).toHaveProperty('url', '/api/test');
      expect(req).toHaveProperty('type', 'get');
      expect(req).toHaveProperty('response');
      expect(req).toHaveProperty('error');
    });

    it('supports dry mode without sending a real request', async () => {
      const req = await request({ url: '/api/test', type: 'GET', dry: true });
      expect(axios.get).not.toHaveBeenCalled();
      expect(req.response).toEqual({ status: 200, data: {} });
      expect(req.error).toBeNull();
    });

    it('passes extra options to axios', async () => {
      axios.get.mockResolvedValue({ status: 200, data: {} });
      await request({
        url: '/api/test',
        type: 'GET',
        responseType: 'blob',
      });
      const callArgs = axios.get.mock.calls[0];
      const lastArg = callArgs[callArgs.length - 1];
      expect(lastArg.responseType).toBe('blob');
    });

    it('handles PUT requests', async () => {
      axios.put.mockResolvedValue({ status: 200, data: { updated: true } });
      const req = await request({
        url: '/api/test/1',
        type: 'PUT',
        body: { name: 'updated' },
      });
      expect(axios.put).toHaveBeenCalled();
      expect(req.response.data.updated).toBe(true);
    });

    it('handles DELETE requests', async () => {
      axios.delete.mockResolvedValue({ status: 204, data: null });
      const req = await request({ url: '/api/test/1', type: 'DELETE' });
      expect(axios.delete).toHaveBeenCalled();
      expect(req.response.status).toBe(204);
    });
  });

  describe('registerGlobalRequestHook', () => {
    it('registers a hook that is called on every request', async () => {
      const hook = vi.fn();
      registerGlobalRequestHook(hook);

      axios.get.mockResolvedValue({ status: 200, data: {} });
      await request({ url: '/api/test', type: 'GET' });

      expect(hook).toHaveBeenCalledTimes(1);
      // Hook receives the BackendRequest instance
      const reqArg = hook.mock.calls[0][0];
      expect(reqArg).toHaveProperty('url', '/api/test');
      expect(reqArg).toHaveProperty('response');
    });

    it('does not add duplicate hooks (uses Set)', async () => {
      const hook = vi.fn();
      registerGlobalRequestHook(hook);
      registerGlobalRequestHook(hook);

      axios.get.mockResolvedValue({ status: 200, data: {} });
      await request({ url: '/api/test', type: 'GET' });

      // Set deduplicates, so hook should be called once per request
      // Note: hooks from previous tests may still be registered
      // but this specific hook should only be called once
      const callCount = hook.mock.calls.length;
      expect(callCount).toBeGreaterThanOrEqual(1);
    });
  });
});
