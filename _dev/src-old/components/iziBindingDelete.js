import Server from "./../commn/Server";

export default function iziBindingDelete() {
    return Server.fetch(prestashop.urls.base_url + 'index.php?fc=module&module=inpostizi&controller=backend&path=inpost/v1/izi/merchant/basket/delete/binding', false, true);
}
// function iziBindingDelete() {
//     return Promise.resolve();
// }
