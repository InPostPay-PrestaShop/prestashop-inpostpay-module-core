import selectorsMap from '../../shared/map/selectorsMap';
import fetchWidgetFromEndpoint from '../http/fetchWidgetFromEndpoint';
import findWidgetInEventHtml from './findWidgetInEventHtml';

/**
 * @param {NodeList} blocks
 * @return {Map<string, HTMLElement[]>}
 */
const groupBlocksByHook = (blocks) => {
  const hookMap = new Map();

  for (const block of blocks) {
    const hookName = block.dataset.hook;

    if (!hookMap.has(hookName)) {
      hookMap.set(hookName, []);
    }

    hookMap.get(hookName).push(block);
  }

  return hookMap;
};

/**
 * @param {string} hookName
 * @param {HTMLElement[]} blocks
 * @param {object} event
 * @param {string} wrapperSelector
 * @param {number} idProductAttribute
 */
const updateHookBlocks = async (hookName, blocks, event, wrapperSelector, idProductAttribute) => {
  const blockSelector = `${wrapperSelector}[data-hook="${hookName}"]`;

  let newContent = findWidgetInEventHtml(event, blockSelector);

  if (!newContent) {
    const { idProduct } = blocks[0].dataset;

    if (!idProduct) {
      throw new Error(`No data-id-product attribute found for block ${blockSelector}`);
    }

    newContent = await fetchWidgetFromEndpoint(
      hookName,
      parseInt(idProduct, 10),
      parseInt(idProductAttribute, 10),
    );
  }

  blocks.forEach((oldBlock, index) => {
    const replacement = index === 0 ? newContent : newContent.cloneNode(true);
    oldBlock.replaceWith(replacement);
  });
};

/**
 * @param {object} event - PrestaShop updatedProduct event
 * @param {int} event.id_product_attribute
 */
const updateProductWidgets = async (event) => {
  const { id_product_attribute: idProductAttribute = 0 } = event;
  const wrapperSelector = selectorsMap().inpostIziProductButtonWrapper;
  const existingBlocks = document.querySelectorAll(`${wrapperSelector}[data-hook]`);

  if (existingBlocks.length === 0) {
    return;
  }

  const hookMap = groupBlocksByHook(existingBlocks);

  await Promise.all(
    Array.from(hookMap.entries()).map(([hookName, blocks]) =>
      updateHookBlocks(hookName, blocks, event, wrapperSelector, idProductAttribute),
    ),
  );
};

export default updateProductWidgets;
