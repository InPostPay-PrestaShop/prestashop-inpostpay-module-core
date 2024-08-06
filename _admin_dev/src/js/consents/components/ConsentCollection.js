import CollectionForm from './../../components/collection-form';

export default class ConsentCollection {
  /**
   * @type {CollectionForm}
   */
  #collection;

  /**
   * @param {HTMLElement} wrapper
   */
  constructor(wrapper) {
    this.wrapper = wrapper;
    this.#collection = new CollectionForm(this.wrapper);
    this.#init();
  }

  #init() {
    this.wrapper.querySelectorAll(CollectionForm.selector).forEach(this.#createLinksForm);

    this.wrapper.addEventListener(CollectionForm.events.entryAdded, (event) => {
      const wrapper = event.detail.querySelector(CollectionForm.selector);

      this.#createLinksForm(wrapper);
    });
  }

  /**
   * @param {HTMLElement} wrapper
   */
  #createLinksForm(wrapper) {
    new CollectionForm(wrapper);
  }
}
