import selectorsMap from "../map/selectorsMap";

/**
 * Dispatch event inpost-update-count
 * @param count {number}
 */
const dispatchInpostUpdateCount = (count = 0) => {
  const { inpostIziButton } = selectorsMap();

  const event = new CustomEvent("inpost-update-count", { detail: count });
  const inpostIziButtons = document.querySelectorAll(inpostIziButton);

  inpostIziButtons.forEach(button => {
    button.dispatchEvent(event);
  });
}

export default dispatchInpostUpdateCount;
