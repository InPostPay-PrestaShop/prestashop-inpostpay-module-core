import Server from "./../commn/Server";

export default function iziBindingDelete() {
    return Server.fetch('/?&fc=module&module=inpostizi&controller=backend&path=inpost/v1/izi/merchant/basket/delete/binding', false, true);
}

// function iziBindingDelete() {
//     return Promise.resolve();
// }
