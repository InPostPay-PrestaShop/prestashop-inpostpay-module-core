class AjaxManager {
  constructor() {
    this.abortControllers = {};
    this.freeze = false;
  }

  bind = () => {
    const elements = document.querySelectorAll("a, button");
    const self = this;

    window.onbeforeunload = function (event) {
      if (!self.freeze) {
        Object.values(self.abortControllers).forEach((controller) => controller.abort());
      }
      return undefined;
    };

    document.addEventListener(
      "click",
      function (event) {
        if (!event.target.matches("#inpostizi-modal-panel-button-link") || event.target.matches("#deepLink")) {
          return;
        }
        self.freeze = true;
        setTimeout(() => {
          self.freeze = false;
        }, 300);
      },
      false
    );
  };

  getSignal = (id) => {
    const newController = new AbortController();
    this.abortControllers[id] = newController;
    return newController.signal;
  };

  abort = (id) => {
    if (this.abortControllers[id]) {
      this.abortControllers[id].abort();
      delete this.abortControllers[id];
    }
  };
}

const ajaxManager = new AjaxManager();
document.addEventListener("DOMContentLoaded", function () {
  ajaxManager.bind();
});
export default ajaxManager;
