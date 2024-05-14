export const randomHex = (size) => {
  if (
    typeof window !== 'undefined' &&
    'crypto' in window &&
    'Uint8Array' in window
  ) {
    const array = new Uint8Array(size)
    window.crypto.getRandomValues(array)
    return [...array].map((n) => n.toString(16)).join('')
  }
  return [...Array(size)]
    .map(() => Math.floor(Math.random() * 16).toString(16))
    .join('')
}
