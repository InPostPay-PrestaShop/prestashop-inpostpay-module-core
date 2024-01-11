
const updateInpostButtonsHandler = () => {
  if (typeof window.handleInpostIziButtons === 'function') {
    window.handleInpostIziButtons();
  }
}

export default updateInpostButtonsHandler;
