import bindIziButtonEvents from "../events/bindIziButtonEvents";

const updateInpostButtonsHandler = () => {
  // setTimeout is in cese when theme is binded to updatedProduct event and replace content of product page by himself
  setTimeout(() => {
    if (typeof window.handleInpostIziButtons === 'function') {
      window.handleInpostIziButtons();
    }

    bindIziButtonEvents();
  }, 10);
}

export default updateInpostButtonsHandler;
