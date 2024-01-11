import selectorsMap from "../map/selectorsMap";

/**
 * Update button count attribute
 * @param count {number}
 */
const updateButtonCount = (count) => {
  const { inpostIziButton } = selectorsMap();
  const inpostIziButtons = document.querySelectorAll(inpostIziButton);

  inpostIziButtons.forEach(button => {
    button.setAttribute('count', count);
  });
}

export default updateButtonCount;
