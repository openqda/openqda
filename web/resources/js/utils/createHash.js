/**
 *
 * @param data {any}
 * @param alg {string=}
 * @param format {string=}
 * @return {Promise<string|null>}
 */
export const createHash = (data, { alg = 'SHA-256', format = 'hex' } = {}) => {
  if (!('crypto' in window)) {
    return Promise.resolve(null);
  }

  try {
    return hash({ data, alg, format });
  } catch {
    return null;
  }
};

const hash = async ({ data, alg, format }) => {
  const str = JSON.stringify(data, null, 0);
  const encoder = new TextEncoder();
  const encoded = encoder.encode(str);
  const hash = await window.crypto.subtle.digest({ name: alg }, encoded);
  const buffer = new Uint8Array(hash);
  switch (format) {
    case 'hex':
      return Array.from(buffer)
        .map((b) => b.toString(16).padStart(2, '0'))
        .join('');
    case 'base64':
      return window.btoa(String.fromCharCode.apply(String, buffer));
    default:
      return String.fromCharCode.apply(String, buffer);
  }
};
