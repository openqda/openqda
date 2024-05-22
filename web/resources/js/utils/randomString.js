export const randomString = async (length = 16) => {
  const array = new Uint32Array(length);
  self.crypto.getRandomValues(array);
  const utf8decoder = new TextDecoder();
  return utf8decoder.decode(array);
};
