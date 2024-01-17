/**
 * Endpoints
 * @type {{
 *   basketConfirmation: string,
 *   basketGetLink: string,
 *   basketPostBinding: string,
 *   orderComplete: string,
 *   basketDeleteBinding: string
 * }}
 */
const endpoints = {
  basketDeleteBinding: 'inpost/v1/izi/merchant/basket/delete/binding',
  basketPostBinding: 'inpost/v1/izi/merchant/basket/post/binding',
  basketConfirmation: 'inpost/v1/izi/merchant/basket/confirmation',
  orderComplete: 'inpost/v1/izi/merchant/order/confirmation/get',
  basketGetLink: 'inpost/v1/izi/merchant/basket/get/link',
}

export default endpoints;
