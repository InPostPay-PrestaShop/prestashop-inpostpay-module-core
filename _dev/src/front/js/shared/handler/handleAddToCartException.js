import selectorsMap from '../map/selectorsMap';
import buildAlertBlock from '../utils/buildAlertBlock';

/**
 * @param error {Error}
 */
const handleAddToCartException = (error) => {
  const { inpostIziProductButtonWrapper, inpostIziAddToCartAlert } = selectorsMap();
  const alertBlock = buildAlertBlock(
    error.message,
    'danger',
    inpostIziAddToCartAlert.replace('.', ''),
  );

  const btnWrapper = document.querySelector(inpostIziProductButtonWrapper);

  if (btnWrapper) {
    const currentAlert = btnWrapper.querySelector(inpostIziAddToCartAlert);

    if (currentAlert) {
      currentAlert.remove();
    }

    btnWrapper.prepend(alertBlock);
  }
};

export default handleAddToCartException;
