import Server from "./../commn/Server";

export default function iziMobileLink() {
  return Server.fetch(
    "index.php?fc=module&module=inpostizi&controller=backend&path=inpost/v1/izi/merchant/basket/get/link",
    false,
    false
  );
}
