export const randomString = (length = 16, provider = 'crypto') => {
  return provider === 'crypto' && 'crypto' in window
    ? randCrypto(length)
    : randRandom(length);
};

const randCrypto = (length) => {
  const array = new Uint32Array(length);
  crypto.getRandomValues(array);
  const utf8decoder = new TextDecoder();
  return utf8decoder.decode(array);
};

const characters =
  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
const randRandom = (length) => {
  let out = '';
  for (let i = 0; i < length; i++) {
    out += characters.charAt(Math.random() * characters.length);
  }
  return out;
};
