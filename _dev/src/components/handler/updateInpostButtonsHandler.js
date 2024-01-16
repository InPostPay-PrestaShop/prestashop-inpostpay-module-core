import bindIziButtonEvents from "../events/bindIziButtonEvents";

const updateInpostButtonsHandler = () => {
  if (typeof window.handleInpostIziButtons === 'function') {
    window.handleInpostIziButtons();
  }

  bindIziButtonEvents();
}

export default updateInpostButtonsHandler;
