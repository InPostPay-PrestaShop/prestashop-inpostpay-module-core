class WidgetError extends Error {
  constructor(code, options) {
    super(code, options);
    this.name = 'WidgetError';
    this.code = code;
  }
}

export default WidgetError;
