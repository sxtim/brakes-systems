const ADD_TO_BASKET_SELECTOR = "[data-add-basket], [data-fls-addtocart-button]";
const BASKET_COUNTER_SELECTOR = "[data-fls-addtocart]";

function getClosestContextNode(node) {
  if (!node) {
    return null;
  }

  return node.closest("[data-context-section-id], [data-context-path], [data-context-label]");
}

function resolveProductId(node) {
  if (!node) {
    return 0;
  }

  if (node.dataset && node.dataset.productId) {
    return parseInt(node.dataset.productId, 10) || 0;
  }

  const productNode = node.closest("[data-product-id], [data-fls-like-product]");
  if (!productNode) {
    return 0;
  }

  if (productNode.dataset.productId) {
    return parseInt(productNode.dataset.productId, 10) || 0;
  }

  if (productNode.dataset.flsLikeProduct) {
    return parseInt(productNode.dataset.flsLikeProduct, 10) || 0;
  }

  return 0;
}

function resolveContext(node) {
  const contextNode = getClosestContextNode(node);
  if (!contextNode || !contextNode.dataset) {
    return {};
  }

  const sectionId = parseInt(contextNode.dataset.contextSectionId || 0, 10) || 0;
  const sectionPath = contextNode.dataset.contextPath || "";
  const label = contextNode.dataset.contextLabel || "";

  const context = {};
  if (sectionId > 0) {
    context.section_id = sectionId;
  }
  if (sectionPath) {
    context.section_path = sectionPath;
  }
  if (label) {
    context.label = label;
  }

  return context;
}

function resolveOptions(node) {
  if (!node || !node.dataset || !node.dataset.options) {
    return null;
  }

  const raw = node.dataset.options;
  if (!raw) {
    return null;
  }

  try {
    return JSON.parse(raw);
  } catch (error) {
    return null;
  }
}

function updateBasketCounter(summary) {
  if (!summary || typeof summary.count === "undefined") {
    return;
  }

  const counter = document.querySelector(BASKET_COUNTER_SELECTOR);
  if (counter) {
    counter.textContent = String(summary.count);
  }
}

function notifyError(message) {
  if (BX?.UI?.Notification?.Center) {
    BX.UI.Notification.Center.notify({
      content: message,
      autoHideDelay: 5000,
      position: "top-right",
    });
  }
}

function handleAddToBasket(event) {
  const button = event.target.closest(ADD_TO_BASKET_SELECTOR);
  if (!button) {
    return;
  }

  event.preventDefault();
  event.stopPropagation();

  if (!BX?.ajax?.runComponentAction) {
    return;
  }

  const productId = resolveProductId(button);
  if (!productId) {
    notifyError("Не удалось определить товар.");
    return;
  }

  const quantity = 1;
  const context = resolveContext(button);
  const options = resolveOptions(button) || null;

  button.classList.add("is-processing");

  BX.ajax.runComponentAction("brakes:basket.actions", "add", {
    mode: "class",
    data: {
      productId,
      quantity,
      context,
      options,
    },
  }).then((response) => {
    const data = response?.data;
    if (!data || data.status !== "success") {
      throw new Error("Unexpected response");
    }

    updateBasketCounter(data.summary || {});
    button.classList.remove("is-processing");
  }).catch(() => {
    notifyError("Не удалось добавить товар в корзину.");
    button.classList.remove("is-processing");
  });
}

document.addEventListener("click", handleAddToBasket, true);

if (BX?.ajax?.runComponentAction) {
  BX.ajax.runComponentAction("brakes:basket.actions", "summary", {
    mode: "class",
  }).then((response) => {
    const data = response?.data;
    if (data?.status === "success") {
      updateBasketCounter(data.summary || {});
    }
  }).catch(() => {});
}
