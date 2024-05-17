export class LoggingHandler {
  async fetch(query, options, documents) {
    console.debug(`[Storage:${options.name}]: fetch`, query, '=>', documents);
    return documents;
  }

  async insert(documents, options, primaries) {
    console.debug(
      `[Storage:${options.name}]: insert`,
      documents,
      '=>',
      primaries
    );
    return primaries;
  }

  async update(documents, modifier, options, updated) {
    console.debug(
      `[Storage:${options.name}]: update`,
      modifier,
      documents,
      '=>',
      updated
    );
    return updated;
  }

  async remove(documents, options, removed) {
    console.debug(
      `[Storage:${options.name}]: update`,
      documents,
      '=>',
      removed
    );
    return removed;
  }
}
