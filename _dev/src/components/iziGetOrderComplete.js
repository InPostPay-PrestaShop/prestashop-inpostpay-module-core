let eventSource;
const url = prestashop.urls.base_url + "index.php?fc=module&module=inpostizi&controller=backend&path=inpost/v1/izi/merchant/order/confirmation/get";

function setupEventSource(url, resolve) {
  if (!eventSource) {
    eventSource = new EventSource(url);
    eventSource.onmessage = (event) => {
      const binding = JSON.parse(event.data);
      if (binding.redirect) {
        resolve({ action: "redirect", redirect: binding.redirect });
      } else if (binding?.action == "delete" || binding?.action == "refresh") {
        resolve({ action: "refresh" });
      }
    };
    eventSource.onerror = (event) => {};
  }
  return eventSource;
}

export default async function iziGetOrderComplete() {
  return new Promise((resolve) => {
    setupEventSource(url, resolve);
  });
}
