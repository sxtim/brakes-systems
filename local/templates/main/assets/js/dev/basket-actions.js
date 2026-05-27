const ADD_TO_BASKET_SELECTOR = "[data-add-basket], [data-fls-addtocart-button]";
const BASKET_COUNTER_SELECTOR = "[data-fls-addtocart]";
const BASKET_URL = "/personal/cart/";

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

function stringifyOptions(options) {
  if (!options || typeof options !== "object") {
    return null;
  }

  try {
    return JSON.stringify(options);
  } catch (error) {
    return null;
  }
}

function buildContextKey(context) {
  const ctx = context && typeof context === "object" ? context : {};
  const sectionId = parseInt(ctx.section_id || 0, 10) || 0;
  const rawPath = String(ctx.section_path || "");
  const sectionPath = rawPath.replace(/^\/+|\/+$/g, "");
  if (sectionId > 0) {
    return `s:${sectionId}`;
  }
  if (sectionPath) {
    return `p:${sectionPath}`;
  }
  return "s:0";
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

function setButtonState(button, inBasket) {
  if (!button) {
    return;
  }

  const textNode = button.querySelector(
    ".main-details__shoping-text, .main-cataloge__shoping-text"
  );

  if (inBasket) {
    if (textNode) {
      textNode.textContent = "В корзине";
    }
    button.classList?.add("is-in-basket");
  } else {
    if (textNode) {
      textNode.textContent = "В корзину";
    }
    button.classList?.remove("is-in-basket");
  }
}

function setButtonsStateByConfig(productId, optionsRaw, context, inBasket) {
  if (!productId) {
    return;
  }
  const targetCtxKey = buildContextKey(context);
  const all = document.querySelectorAll(ADD_TO_BASKET_SELECTOR);
  all.forEach((btn) => {
    const pid = resolveProductId(btn);
    if (pid !== productId) {
      return;
    }
    const btnOptions = btn?.dataset?.options || "";
    if ((optionsRaw || "") !== (btnOptions || "")) {
      return;
    }
    const btnCtxKey = buildContextKey(resolveContext(btn));
    if (btnCtxKey !== targetCtxKey) {
      return;
    }
    setButtonState(btn, inBasket);
  });
}

function syncButtonsWithBasket() {
  if (!BX?.ajax?.runComponentAction) {
    return;
  }

  const buttons = Array.from(document.querySelectorAll(ADD_TO_BASKET_SELECTOR));
  if (buttons.length === 0) {
    return;
  }

  const items = [];
  const boundButtons = [];

  buttons.forEach((button) => {
    const productId = resolveProductId(button);
    if (!productId) {
      return;
    }
    items.push({
      productId,
      options: button?.dataset?.options || "",
      context: resolveContext(button),
    });
    boundButtons.push(button);
  });

  if (items.length === 0) {
    return;
  }

  BX.ajax
    .runComponentAction("brakes:basket.actions", "check", {
      mode: "class",
      data: { items },
    })
    .then((response) => {
      const data = response?.data;
      if (!data || data.status !== "success") {
        return;
      }

      updateBasketCounter(data.summary || {});

      const states = Array.isArray(data.items) ? data.items : [];
      states.forEach((state, index) => {
        const btn = boundButtons[index];
        if (!btn) {
          return;
        }
        setButtonState(btn, Boolean(state?.inBasket));
      });
    })
    .catch(() => {});
}

function notifyError(message) {
  let text = message;
  if (Array.isArray(text)) {
    text = text.filter(Boolean).join(", ");
  } else if (text && typeof text === "object") {
    try {
      text = JSON.stringify(text);
    } catch (error) {
      text = String(text);
    }
  }
  if (typeof text !== "string" || text.trim() === "") {
    text = "Не удалось добавить товар в корзину.";
  }

  const show = () => {
    if (!BX?.UI?.Notification?.Center) {
      return;
    }

    BX.UI.Notification.Center.notify({
      content: text,
      autoHideDelay: 5000,
      position: "top-right",
    });
  };

  if (BX?.UI?.Notification?.Center) {
    show();
    return;
  }

  if (BX?.Runtime?.loadExtension) {
    BX.Runtime.loadExtension("ui.notification").then(show).catch(() => {});
  }
}

function handleAddToBasket(event) {
  const button = event.target.closest(ADD_TO_BASKET_SELECTOR);
  if (!button) {
    return;
  }

  event.preventDefault();
  event.stopPropagation();

  if (button.classList?.contains("is-processing")) {
    return;
  }

  if (button.classList && button.classList.contains("is-in-basket")) {
    window.location.href = BASKET_URL;
    return;
  }

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
  const optionsPayload = options ? stringifyOptions(options) : null;

  button.classList.add("is-processing");

  BX.ajax.runComponentAction("brakes:basket.actions", "add", {
    mode: "class",
    data: {
      productId,
      quantity,
      context,
      options: optionsPayload,
    },
  }).then((response) => {
    const data = response?.data;
    if (!data || data.status !== "success") {
      const errors = Array.isArray(data?.errors) ? data.errors.join(", ") : "";
      notifyError(errors || "Не удалось добавить товар в корзину.");
      button.classList.remove("is-processing");
      return;
    }

    updateBasketCounter(data.summary || {});
    setButtonState(button, true);
    // Keep UI consistent on pages with repeated cards (catalog/search/favorites/etc).
    setButtonsStateByConfig(productId, optionsPayload, context, true);
    button.classList.remove("is-processing");
  }).catch(() => {
    notifyError("Не удалось добавить товар в корзину.");
    button.classList.remove("is-processing");
  });
}

document.addEventListener("click", handleAddToBasket, true);

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", syncButtonsWithBasket);
} else {
  syncButtonsWithBasket();
}

// When returning via back/forward cache, the DOM can be restored with stale "В корзину" labels.
// Re-check basket state to keep buttons consistent with header counter.
window.addEventListener("pageshow", (event) => {
  const nav = performance.getEntriesByType("navigation")[0];
  if (event?.persisted || nav?.type === "back_forward") {
    syncButtonsWithBasket();
  }
});
