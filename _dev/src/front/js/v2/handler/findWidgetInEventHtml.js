import parseToHtml from '../../shared/utils/parseToHtml';

/**
 * @param {string} html
 * @param {string} blockSelector
 * @return {HTMLElement|null}
 */
const extractWidget = (html, blockSelector) => {
  const dom = parseToHtml(html);

  if (!dom) {
    return null;
  }

  return dom.matches?.(blockSelector) ? dom : dom.querySelector?.(blockSelector);
};

/**
 * @param {object} event
 * @param {string} blockSelector
 * @return {HTMLElement|null}
 */
const findWidgetInEventHtml = (event, blockSelector) => {
  for (const value of Object.values(event)) {
    if (typeof value === 'string' && value !== '') {
      const match = extractWidget(value, blockSelector);

      if (match) {
        return match;
      }
    }
  }

  return null;
};

export default findWidgetInEventHtml;
