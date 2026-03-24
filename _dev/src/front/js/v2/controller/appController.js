import widgetOptionsBuilder from '../builder/widgetOptionsBuilder';
import getBindingApiKey from '../handler/getBindingApiKey';
import unboundWidgetClicked from '../handler/unboundWidgetClicked';
import updateProductWidgets from '../handler/updateProductWidgets';
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
      setLanguage,
      build,
    } = widgetOptionsBuilder();

    if (typeof window.prestashop.language.iso_code !== 'undefined') {
      setLanguage(window.prestashop.language.iso_code);
    }

    setMerchantClientId(window.inpostizi_merchant_client_id);
    setBasketBindingApiKey(getBindingApiKey());
    setUnboundWidgetClicked(unboundWidgetClicked);
    setHandleBasketEvent(handleBasketEvent);

    initWidget(build());

    window.prestashop.on('updatedCart', () => refreshWidget());
    window.prestashop.on('updatedProduct', async (event) => {
      // Prevent from running inside quick view
      if (typeof event.is_quick_view !== 'undefined' && event.is_quick_view) {
        return;
      }

      try {
        await updateProductWidgets(event);
      } catch (error) {
        // eslint-disable-next-line no-console
        console.error(error);
      }

      refreshWidget();
    });
  };

  return {
    init,
  };
};

export default appController;
