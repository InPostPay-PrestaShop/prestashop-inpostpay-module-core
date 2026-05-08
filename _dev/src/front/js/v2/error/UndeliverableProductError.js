import WidgetError from './WidgetError';

class UndeliverableProductError extends WidgetError {
  constructor(options) {
    super('UNDELIVERABLE_PRODUCT', options);
    this.name = 'UndeliverableProductError';
  }
}

export default UndeliverableProductError;
