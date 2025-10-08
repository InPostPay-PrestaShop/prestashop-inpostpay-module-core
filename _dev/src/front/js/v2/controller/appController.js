import widgetOptionsBuilder from '../builder/widgetOptionsBuilder';
import getBindingApiKey from '../handler/getBindingApiKey';
import unboundWidgetClicked from '../handler/unboundWidgetClicked';
import updateProductActionsBlock from '../handler/updateProductActionsBlock';
import handleBasketEvent from '../handler/handleBasketEvent';
import widget from '../components/widget';

const appController = () => {
  const init = () => {
    const { init: initWidget, refresh: refreshWidget } = widget();
    const {
      setMerchantClientId,
      setBasketBindingApiKey,
      setUnboundWidgetClicked,
      setHandleBasketEvent,
      setIsWidgetSplitBoundEnabled,
      setAddToBasketClicked,
      setLanguage,
      build,
    } = widgetOptionsBuilder();

    if (typeof window.prestashop.language.iso_code !== 'undefined') {
      setLanguage(window.prestashop.language.iso_code);
    }

    setMerchantClientId(window.inpostizi_merchant_client_id);
    setIsWidgetSplitBoundEnabled(window.inpostizi_widget_split_bound_enabled);
    setBasketBindingApiKey(getBindingApiKey());
    setUnboundWidgetClicked(unboundWidgetClicked);
    setHandleBasketEvent(handleBasketEvent);
    setAddToBasketClicked(unboundWidgetClicked);

    initWidget(build());

    window.prestashop.on('updatedCart', () => refreshWidget());
    window.prestashop.on('updatedProduct', (event) => {
      updateProductActionsBlock(event)
      refreshWidget()
    });
  };

  return {
    init,
  };
};

export default appController;
