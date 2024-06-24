class ConsentCollection {
  #selectors = {
    itemContainer: '.js-consent-collection-item-container',
    item: '.js-consent-collection-item',
    addButton: '.js-add-consent-collection-item',
    removeButton: '.js-remove-consent-collection-item',
    errorLimitConsent: '.js-consent-limit'
  };
  #nextIndex = 0;
  #itemContainer;

  /**
   * @param {HTMLElement} wrapper
   */
  constructor(wrapper) {
    this.wrapper = wrapper;
    this.#itemContainer = this.wrapper.querySelector(this.#selectors.itemContainer);
    this.#nextIndex = this.#itemContainer.querySelectorAll(this.#selectors.item).length;
    this.#init();
  }

  #init() {
    this.#validateAddButton();

    this.wrapper
      .querySelector(this.#selectors.addButton)
      .addEventListener('click', () => this.#addItem());

    this.wrapper
      .querySelectorAll(this.#selectors.removeButton)
      .forEach((button) => button.addEventListener('click', (event) => {
        this.#removeItem(event.target);
      }));
  }

  #addItem() {
    const item = document.createElement('div');

    item.classList.add('js-consent-collection-item');
    item.innerHTML = this.wrapper
      .dataset
      .prototype
      .replace(/__name__/g, this.#nextIndex++);

    this.#itemContainer.append(item);

    item
      .querySelector(this.#selectors.removeButton)
      .addEventListener('click', (event) => {
        this.#removeItem(event.target);
      });

    this.#validateAddButton();
  }

  #removeItem(button) {
    button.closest(this.#selectors.item).remove();
    this.#validateAddButton();
  }

  #validateAddButton() {
    const isLimitReached = this.#itemContainer.querySelectorAll(this.#selectors.item).length >= 10;
    document.querySelector(this.#selectors.addButton).disabled = isLimitReached;
    document.querySelector(this.#selectors.errorLimitConsent).classList.toggle('d-none', !isLimitReached);
  }
}

export default ConsentCollection;
