import ajaxManager from "./AjaxManager.js";

export default class Server {
  static fetch(url, isPost, isDelete, data, id) {
    if (isPost) {
      data = data || {};
      return fetch(url, {
        method: "POST",
        signal: ajaxManager.getSignal(),
        credentials: "same-origin",
        body: JSON.stringify(data),
      })
        .then((data) => data.json())
        .then((data) => {
          if (typeof data === "object") {
            return data;
          }
          return JSON.parse(data);
        })
        .catch((error) => {
          return {};
        });
    } else if (isDelete) {
      return fetch(url, {
        method: "DELETE",
        signal: ajaxManager.getSignal(),
        credentials: "same-origin",
      })
        .then((data) => data.json())
        .then((data) => JSON.parse(data))
        .catch((error) => {
          return {};
        });
    } else {
      return fetch(url, {
        signal: ajaxManager.getSignal(id),
        credentials: "same-origin",
      })
        .then((data) => data.json())
        .then((data) => {
          if (typeof data === "string") {
            return JSON.parse(data);
          }
          return data;
        })
        .catch((error) => {
          return {};
        });
    }
  }
}
