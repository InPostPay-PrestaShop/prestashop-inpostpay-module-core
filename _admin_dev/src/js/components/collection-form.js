const collectionEntryClass = 'js-collection-entry';

export default class CollectionForm {
  static selector = '.js-collection-form';

  static events = {
    entryAdded: 'collectionEntryAdded',
    entryRemoved: 'collectionEntryRemoved',
  };

  #selectors = {
    entries: '.js-collection-entries-container',
    entry: `.${collectionEntryClass}`,
    addEntry: '.js-add-collection-entry',
    removeEntry: '.js-remove-collection-entry',
    maxCountMessage: '.js-max-collection-count-message',
  };

  /**
   * @type {number|null}
   */
  #maxCount;

  /**
   * @type {HTMLElement}
   */
  #entries;

  /**
   * @type {number}
   */
  #count;

  /**
   * @type {number}
   */
  #nextIndex;

  /**
   * @type {HTMLButtonElement|null}
   */
  #addEntryButton = null;

  /**
   * @param {HTMLElement} wrapper
   */
  constructor(wrapper) {
    this.wrapper = wrapper;
    this.#entries = this.#find(this.#selectors.entries);
    this.#count = this.#findAll(this.#selectors.entry, this.#entries).length
    this.#maxCount = 'maxCount' in this.wrapper.dataset ? Number(this.wrapper.dataset.maxCount) : null;
    this.#nextIndex = this.#count;
    this.#addEntryButton = this.#find(this.#selectors.addEntry);

    this.#init();
  }

  hasMaxEntries() {
    return null !== this.#maxCount && this.#count >= this.#maxCount;
  }

  #init() {
    if (null !== this.#addEntryButton) {
      this.#addEntryButton.addEventListener('click', () => this.#addEntry());
    }

    this.#findAll(this.#selectors.removeEntry)
      .forEach((button) => this.#initRemoveEntryButtonListeners(button));

    this.#updateAddEntryButtonState();
  }

  #addEntry() {
    if (null !== this.#maxCount && this.#count >= this.#maxCount) {
      throw new Error('Cannot add a new collection entry.');
    }

    const entry = document.createElement('div');

    entry.classList.add(collectionEntryClass);
    entry.innerHTML = this.wrapper
      .dataset
      .prototype
      .replace(/__name__/g, this.#nextIndex++);

    this.#entries.append(entry);
    ++this.#count;

    const removeEntryButton = entry.querySelector(this.#selectors.removeEntry);

    if (null !== removeEntryButton) {
      this.#initRemoveEntryButtonListeners(removeEntryButton);
    }

    this.#updateAddEntryButtonState();

    this.wrapper.dispatchEvent(new CustomEvent(CollectionForm.events.entryAdded, {
      detail: entry,
    }));
  }

  #initRemoveEntryButtonListeners(button) {
    button.addEventListener('click', (event) => {
      this.#removeEntry(event.target);
    });
  }

  /**
   * @param {HTMLButtonElement} button
   */
  #removeEntry(button) {
    const entry = button.closest(this.#selectors.entry);

    entry.remove();
    --this.#count;

    this.#updateAddEntryButtonState();

    this.wrapper.dispatchEvent(new CustomEvent(CollectionForm.events.entryRemoved, {
      detail: entry,
    }));
  }

  #updateAddEntryButtonState() {
    if (null === this.#addEntryButton || null === this.#maxCount) {
      return;
    }

    const messageWrapper = this.#find(this.#selectors.maxCountMessage);

    this.#addEntryButton.disabled = this.#count >= this.#maxCount;
    if (null !== messageWrapper) {
      messageWrapper.classList.toggle('d-none', !this.#addEntryButton.disabled);
    }
  }

  /**
   * @param {string} selector
   * @param {HTMLElement} parent
   *
   * @return {Element[]} elements that do not belong to nested forms
   */
  #findAll(selector, parent = this.wrapper) {
    const elements = parent.querySelectorAll(selector);

    return Array.from(elements).filter((element) => {
      const wrapper = element.closest(CollectionForm.selector);

      return wrapper === this.wrapper;
    });
  }

  /**
   * @param {string} selector
   * @param {HTMLElement} parent
   *
   * @return {Element|null} element that does not belong to a nested form
   */
  #find(selector, parent = this.wrapper) {
    return this.#findAll(selector, parent).at(0) ?? null;
  }
}
