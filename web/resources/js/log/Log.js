const { LOG_LEVEL } = import.meta.env;

class Log {
  constructor(name) {
    this.name = name;
  }

  debug(...args) {
    runLog(LOG_LEVEL >= 4, 'debug', this.name, args);
  }

  info(...args) {
    runLog(LOG_LEVEL >= 3, 'info', this.name, args);
  }

  log(...args) {
    runLog(LOG_LEVEL >= 2, 'log', this.name, args);
  }

  warn(...args) {
    runLog(LOG_LEVEL >= 1, 'warn', this.name, args);
  }

  error(...args) {
    runLog(LOG_LEVEL >= 0, 'error', this.name, args);
  }
}

const toLine = (name, args) => [name].concat(args).join(' ');
const runLog = (active, type, name, args) => {
  if (!active) {
    return;
  }
  const line = toLine(name, args);
  console[type].call(console, line);
};

export const createLog = (name) => new Log(name);
