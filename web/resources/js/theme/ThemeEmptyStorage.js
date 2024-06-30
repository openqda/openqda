/**
 * @type {ThemeStorage}
 */
export class ThemeEmptyStorage {
  async isDefined() {
    return false;
  }
  async update() {
    return false;
  }
  async value() {
    return null;
  }
  async remove() {
    return false;
  }
}
