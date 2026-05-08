import getBindingApiKey from './getBindingApiKey';
import selectorsMap from '../../shared/map/selectorsMap';
import addToCartHandler from './addToCartHandler';
import WidgetError from '../error/WidgetError';

const getProductFormData = (form) => {
  const formData = new FormData(form);
  const data = {};
  const group = [];

  for (const [key, value] of formData.entries()) {
    if (key.indexOf('group') === 0) {
      group.push({
        key,
        value: encodeURIComponent(value),
      });
    } else {
      data[key] = value;
    }
  }

  data.group = group;

  return data;
};

const handleBindingApiKey = (forceNew = false) =>
  new Promise((resolve, reject) => {
    const binding = getBindingApiKey(forceNew);

    if (binding instanceof Promise) {
      binding
        .then((key) => {
          resolve(key);
        })
        .catch((err) => {
          reject(err);
        });

      return;
    }

    resolve(binding);
  });

const unboundWidgetClicked = (productId) =>
  new Promise((resolve, reject) => {
    if (!productId) {
      handleBindingApiKey(true)
        .then((key) => {
          resolve(key);
        })
        .catch((err) => {
          reject(err);
        });

      return;
    }

    const { productPageForm } = selectorsMap();
    const formElement = document.querySelector(productPageForm);

    if (!formElement) {
      reject(new WidgetError('PRODUCT_FORM_NOT_FOUND'));
      return;
    }

    const { group, ...restFormData } = getProductFormData(formElement);
    const formData = {
      add: 1,
      action: 'update',
      ajax: 1,
      ...restFormData,
    };

    group.forEach((item) => {
      formData[item.key] = item.value;
    });

    addToCartHandler(formData)
      .then(() => {
        handleBindingApiKey(true)
          .then((key) => {
            resolve(key);
          })
          .catch((err) => {
            reject(err);
          });
      })
      .catch((err) => {
        if (err instanceof WidgetError) {
          reject(err);
        } else {
          reject(new WidgetError('ADD_TO_CART_FAILED', { cause: err }));
        }
      });
  });

export default unboundWidgetClicked;
