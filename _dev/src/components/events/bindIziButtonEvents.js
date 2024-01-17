import iziBindingCompleteHandler from "../handler/inziBindingCompleteHandler";

const bindIziButtonEvents = () => {
  const inpostButtons = document.querySelectorAll('inpost-izi-button');

  inpostButtons.forEach((inpostButton) => {
    inpostButton.removeEventListener('izi-binding-complete', iziBindingCompleteHandler);
    inpostButton.addEventListener('izi-binding-complete', iziBindingCompleteHandler);
  });
}

export default bindIziButtonEvents;
