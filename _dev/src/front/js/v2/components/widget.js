let instance;

const widget = () => {
  /**
   * @param widgetConfig {object}
   */
  const init = (widgetConfig) => {
    instance = window.InPostPayWidget.init(widgetConfig);
  };

  const refresh = () => {
    if (typeof instance === 'undefined') {
      throw new Error('Widget is not initialized yet, use init() method first');
    }

    instance.refresh();
  };

  return {
    init,
    refresh,
  };
};

export default widget;
