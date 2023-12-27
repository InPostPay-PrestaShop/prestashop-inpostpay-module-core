let eventSource;

function setupEventSource(resolve) {
  if (eventSource) {
    eventSource.close();
  }

  eventSource = new EventSource(
    `${prestashop.urls.base_url}index.php?fc=module&module=inpostizi&controller=backend&path=inpost/v1/izi/merchant/basket/confirmation`
  );

  eventSource.onmessage = (event) => {
    let binding = JSON.parse(event.data);
    if (typeof binding === "string") {
      binding = JSON.parse(binding);
    }
    if (binding?.phone_number) {
      eventSource.close();
      resolve(binding);
    } else {
      return;
    }
  };

  eventSource.onerror = (event) => {};
}

async function iziGetIsBound() {
  return new Promise((resolve) => {
    setupEventSource(resolve);
  });
}

export default iziGetIsBound;
