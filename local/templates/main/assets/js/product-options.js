const PRICE_UPDATE_DELAY = 150;
const PRICE_DECODE_HELPER = document.createElement("span");
const pendingPriceTimers = new Map();
const OPTION_DEFAULTS = Object.freeze({
  two_piece_disc_construction: "no",
  rotor_pattern: "none",
  caliper_logo: "standard",
  electric_handbrake: "no",
});
const LOG_ENABLED = false;

function logDebug(...args) {
  if (!LOG_ENABLED) {
    return;
  }
  console.debug(...args);
}

function logWarn(...args) {
  if (!LOG_ENABLED) {
    return;
  }
  console.warn(...args);
}

function logError(...args) {
  if (!LOG_ENABLED) {
    return;
  }
  console.error(...args);
}

function toLowerString(value) {
  if (typeof value === "string") {
    return value.toLowerCase();
  }

  if (value === null || value === undefined) {
    return "";
  }

  return String(value).toLowerCase();
}

function normalizeOptionsPayload(value) {
  if (!value) {
    return null;
  }

  let payload = value;

  if (typeof payload === "string") {
    try {
      payload = JSON.parse(payload);
    } catch (error) {
      return null;
    }
  }

  if (payload && typeof payload === "object" && payload.options && typeof payload.options === "object") {
    payload = payload.options;
  }

  if (!payload || typeof payload !== "object") {
    return null;
  }

  const normalized = {};
  Object.keys(payload).forEach((key) => {
    if (!key) {
      return;
    }
    const normalizedKey = toLowerString(key);
    let rawValue = payload[key];
    if (rawValue && typeof rawValue === "object" && Object.prototype.hasOwnProperty.call(rawValue, "value")) {
      rawValue = rawValue.value;
    } else if (rawValue && typeof rawValue === "object" && Object.prototype.hasOwnProperty.call(rawValue, "VALUE")) {
      rawValue = rawValue.VALUE;
    }

    if (rawValue === undefined || rawValue === null) {
      return;
    }

    normalized[normalizedKey] = toLowerString(rawValue);
  });

  if (Object.keys(normalized).length === 0) {
    return null;
  }

  return { options: normalized };
}

function cloneOptionsPayload(input) {
  const normalized = normalizeOptionsPayload(input);
  if (!normalized) {
    return null;
  }

  return {
    options: { ...normalized.options },
  };
}

function mergeOptionsPayload(...payloads) {
  const merged = {};

  payloads.forEach((payload) => {
    const normalized = normalizeOptionsPayload(payload);
    if (normalized && normalized.options) {
      Object.assign(merged, normalized.options);
    }
  });

  return Object.keys(merged).length > 0 ? { options: merged } : null;
}

function stringifyOptionsPayload(payload) {
  const normalized = normalizeOptionsPayload(payload);
  if (!normalized) {
    return null;
  }

  try {
    return JSON.stringify(normalized);
  } catch (error) {
    logWarn("[ProductOptions]", "Failed to stringify options payload", error, payload);
    return null;
  }
}

function extractPriceFromMeta(productId) {
  const meta = window.__FAVORITES__?.meta;
  if (!meta || typeof meta !== "object") {
    return null;
  }

  const entry = meta[productId];
  if (!entry || typeof entry !== "object") {
    return null;
  }

  const price = entry.price;
  if (!price || typeof price !== "object") {
    return null;
  }

  if (typeof price.formatted === "string") {
    return price.formatted;
  }

  if (typeof price.PRICE_FORMATTED === "string") {
    return price.PRICE_FORMATTED;
  }

  return null;
}

document.addEventListener("click", (event) => {
  const spoller = event.target.closest(".spollers__item");
  const optionNode = event.target.closest(".main-cataloge__sublist-item");
  if (!spoller || !optionNode) {
    return;
  }

  event.preventDefault();
  event.stopPropagation();

  spoller.querySelectorAll(".main-cataloge__sublist-item").forEach((item) => item.classList.remove("selected"));
  optionNode.classList.add("selected");

  const container =
    spoller.closest(".main-cataloge__item") ||
    spoller.closest(".main__details") ||
    spoller.closest(".main-details") ||
    document.querySelector(".main__details") ||
    document.querySelector(".main-details") ||
    document;

  const options = updateProductOptions(container, "click");
  schedulePriceUpdate(container, options, "click");
});

document.addEventListener("DOMContentLoaded", () => {
  const catalogItems = new Set();
  document.querySelectorAll(".main-cataloge__item").forEach((card) => {
    if (catalogItems.has(card)) {
      return;
    }
    catalogItems.add(card);
    const options = updateProductOptions(card, "init-card");
    schedulePriceUpdate(card, options, "init-card");
  });

  const detailItems = new Set();
  document.querySelectorAll(".main__details, .main-details").forEach((details) => {
    if (detailItems.has(details)) {
      return;
    }
    detailItems.add(details);
    const options = updateProductOptions(details, "init-details");
    schedulePriceUpdate(details, options, "init-details");
  });

  handleOneClickBuyButtons();
});

function handleOneClickBuyButtons() {
  document.querySelectorAll('[data-fls-popup-link="speedBuy"]').forEach((button) => {
    button.addEventListener("click", function (event) {
      const buyButton = event.currentTarget;

      const popup = document.querySelector('.popup[data-fls-popup="speedBuy"]');
      if (!popup) {
        return;
      }

      const productName = buyButton.dataset.productName || "";
      const productPrice = buyButton.dataset.productPrice || "";
      const productUrl = buyButton.dataset.productUrl || "";
      const options = buyButton.dataset.options || "";

      const itemContainer =
        buyButton.closest(".main-cataloge__item") ||
        buyButton.closest(".main__details") ||
        buyButton.closest(".main-details");
      const priceElement = itemContainer ? itemContainer.querySelector(".main-details__price-new, .main-cataloge__price") : null;
      const actualPrice = priceElement ? priceElement.textContent.trim() : decodeHtml(productPrice);

      let optionsString = "";
      const keyMap = {
        two_piece_disc_construction: "Двусоставная конструкция диска",
        rotor_pattern: "Рисунок ротора",
        caliper_logo: "Лого на суппорт",
        electric_handbrake: "Электроручник",
      };
      const valueMap = {
        no: "Нет",
        yes: "Да",
        standard: "Стандартный",
        special: "Особый логотип",
        perforation: "Перфорация",
        slots: "Насечки",
        perforation_slots: "Перфорация + насечки",
      };

      try {
        if (options && options !== "{}") {
          const normalizedOptions = normalizeOptionsPayload(options);
          const optionsMap = normalizedOptions?.options || {};
          const optionsArray = [];
          Object.keys(optionsMap).forEach((key) => {
            const rawValue = optionsMap[key];
            if (keyMap[key] && rawValue) {
              const translatedValue = valueMap[rawValue.toLowerCase()] || rawValue;
              optionsArray.push(`${keyMap[key]}: ${translatedValue}`);
            }
          });
          optionsString = optionsArray.join(", ");
        }
      } catch (error) {
        optionsString = options || "Ошибка чтения опций";
      }

      const productNameInput = popup.querySelector('input[data-product-input="name"]');
      const productPriceInput = popup.querySelector('input[data-product-input="price"]');
      const productUrlInput = popup.querySelector('input[data-product-input="url"]');
      const productOptionsInput = popup.querySelector('input[data-product-input="options"]');

      if (productNameInput) productNameInput.value = productName;
      if (productPriceInput) productPriceInput.value = actualPrice;
      if (productUrlInput) productUrlInput.value = productUrl;
      if (productOptionsInput) productOptionsInput.value = optionsString;
    });
  });
}

function updateProductOptions(productContainer, reason = "manual") {
  const scope = productContainer instanceof Element ? productContainer : document;
  const productId = getProductIdFromContainer(scope);
  const fallbackOptions = getInitialOptions(scope, productId);
  const fallbackClone = cloneOptionsPayload(fallbackOptions);

  let optionsPayload = null;
  if ((reason === "init-card" || reason === "init-details") && fallbackClone) {
    optionsPayload = fallbackClone;
  } else {
    optionsPayload = collectSelectedOptions(scope, fallbackClone || fallbackOptions, reason);
  }

  const normalizedOptions = cloneOptionsPayload(optionsPayload) || { options: {} };
  setOptionsAttribute(scope, normalizedOptions);

  if (productId) {
    document.dispatchEvent(new CustomEvent("productOptions:changed", {
      detail: {
        productId,
        options: normalizedOptions,
        reason,
      },
    }));
  }

  return {
    productId,
    options: { ...normalizedOptions.options },
    reason,
    timestamp: Date.now(),
  };
}

function collectSelectedOptions(scope, defaults = null, reason = "manual") {
  const selectedOptions = { ...OPTION_DEFAULTS };
  const normalizedDefaults = normalizeOptionsPayload(defaults);
  if (normalizedDefaults && normalizedDefaults.options) {
    Object.assign(selectedOptions, normalizedDefaults.options);
  }

  (scope instanceof Element ? scope : document).querySelectorAll(".spollers__item").forEach((spoller) => {
    const selectedOption = spoller.querySelector(".main-cataloge__sublist-item.selected");
    if (!selectedOption) {
      return;
    }

    const optionText = selectedOption.textContent.trim();
    const featureTitleElement = spoller.querySelector(".main-cataloge__feature-item, .main-details__feature-item");
    if (!featureTitleElement) {
      return;
    }

    const featureTitle = featureTitleElement.textContent.trim();

    let englishKey = featureTitle;
    let englishValue = optionText;

    if (featureTitle.includes("Двусоставная конструкция диска")) {
      englishKey = "two_piece_disc_construction";
      englishValue = optionText.toLowerCase() === "да" ? "yes" : "no";
    } else if (featureTitle.includes("Рисунок ротора")) {
      englishKey = "rotor_pattern";
      if (optionText.toUpperCase() === "НЕТ") {
        englishValue = "none";
      } else if (optionText.toUpperCase().includes("ПЕРФОРАЦИЯ") && optionText.toUpperCase().includes("НАСЕЧКИ")) {
        englishValue = "perforation_slots";
      } else if (optionText.toUpperCase().includes("ПЕРФОРАЦИЯ")) {
        englishValue = "perforation";
      } else if (optionText.toUpperCase().includes("НАСЕЧКИ")) {
        englishValue = "slots";
      }
    } else if (featureTitle.includes("Лого на суппорт")) {
      englishKey = "caliper_logo";
      if (optionText.toLowerCase().includes("особ")) {
        englishValue = "special";
      } else if (optionText.toLowerCase().includes("логотип")) {
        englishValue = "custom_logo";
      } else {
        englishValue = "standard";
      }
    } else if (featureTitle.includes("Электроручник")) {
      englishKey = "electric_handbrake";
      englishValue = optionText.toLowerCase() === "да" ? "yes" : "no";
    }

    if (englishKey) {
      selectedOptions[englishKey.toLowerCase()] = toLowerString(englishValue);
    }
  });

  return { options: selectedOptions };
}

function setOptionsAttribute(scope, payload) {
  const holder = scope instanceof Element ? scope : document;
  if (!holder) {
    return;
  }

  const value = stringifyOptionsPayload(payload);
  if (typeof value !== "string" || value === "") {
    return;
  }

  const buyButtons = holder.querySelectorAll('[data-fls-addtocart-button], [data-fls-popup-link="speedBuy"], .main-details__buy, .main-cataloge__shoping-btn');
  const favoriteButtons = holder.querySelectorAll("[data-fls-like-button]");

  buyButtons.forEach((button) => {
    button.setAttribute("data-options", value);
  });

  favoriteButtons.forEach((button) => {
    button.setAttribute("data-options", value);
  });
}

function getInitialOptions(scope, productId) {
  const datasetPayload = extractOptionsFromDataset(scope, productId);
  const metaPayload = productId ? extractOptionsFromMeta(productId) : null;

  const merged = mergeOptionsPayload(datasetPayload, metaPayload);

  if (!datasetPayload && merged) {
    setOptionsAttribute(scope, merged);
  }

  return merged;
}

function extractOptionsFromDataset(scope, productId) {
  const selectorParts = [];
  if (productId) {
    selectorParts.push(`[data-fls-like-button][data-product-id="${productId}"]`);
    selectorParts.push(`[data-fls-like-button][data-fls-like-product="${productId}"]`);
  }
  selectorParts.push("[data-fls-like-button]");
  const selector = selectorParts.join(", ");

  const candidates = [];
  if (scope instanceof Element) {
    candidates.push(...scope.querySelectorAll(selector));
  } else {
    document.querySelectorAll(selector).forEach((node) => candidates.push(node));
  }

  const button = candidates.find((node) => node.dataset && (
    (productId && (node.dataset.productId === String(productId) || node.dataset.flsLikeProduct === String(productId))) ||
    productId === null
  )) || candidates[0];

  if (!button || !button.dataset.options) {
    return null;
  }

  return normalizeOptionsPayload(button.dataset.options);
}

function extractOptionsFromMeta(productId) {
  const meta = window.__FAVORITES__?.meta;
  if (!meta || typeof meta !== "object") {
    return null;
  }

  const entry = meta[productId];
  if (!entry || typeof entry !== "object") {
    return null;
  }

  return normalizeOptionsPayload(entry.options || entry);
}

function schedulePriceUpdate(productContainer, optionsData, reason = "manual") {
  let targetContainer = productContainer instanceof Element ? productContainer : null;
  const lookupId = optionsData && typeof optionsData.productId !== "undefined"
    ? optionsData.productId
    : null;

  if (!targetContainer && lookupId) {
    targetContainer = document.querySelector(`[data-fls-like-product="${lookupId}"]`);
  }

  const productId = lookupId || getProductIdFromContainer(targetContainer || productContainer);
  if (!productId) {
    return;
  }

  if (!targetContainer) {
    targetContainer = document.querySelector(`[data-fls-like-product="${productId}"]`)
      || document.querySelector(`[data-product-id="${productId}"]`);
  }

  if (!targetContainer) {
    return;
  }

  if ((reason === "init-card" || reason === "init-details")) {
    const metaPrice = extractPriceFromMeta(productId);
    if (metaPrice !== null && metaPrice !== undefined) {
      applyPriceToContainer(targetContainer, metaPrice, productId);
      return;
    }
  }

  const key = String(productId);
  if (pendingPriceTimers.has(key)) {
    clearTimeout(pendingPriceTimers.get(key));
  }

  const payload = { options: { ...(optionsData?.options || {}) } };

  const timer = setTimeout(() => {
    pendingPriceTimers.delete(key);
    requestPriceUpdate(targetContainer, productId, payload, reason);
  }, PRICE_UPDATE_DELAY);

  pendingPriceTimers.set(key, timer);
}

function requestPriceUpdate(productContainer, productId, optionsPayload, reason = "manual") {
  if (typeof BX === "undefined" || !BX.ajax || typeof BX.ajax.runComponentAction !== "function") {
    return;
  }

  logDebug("[ProductOptions]", "requestPriceUpdate", { productId, reason, options: optionsPayload });
  BX.ajax.runComponentAction("brakes:favorites.sync", "calculate", {
    mode: "class",
    data: { productId, options: optionsPayload },
  }).then((response) => {
    const transportStatus = response?.status || null;
    const payload = response?.data ?? response ?? null;

    if (transportStatus && transportStatus !== "success") {
      logWarn("[ProductOptions]", "calculate transport error", { productId, reason, response });
      return;
    }

    if (!payload || (payload.status && payload.status !== "success")) {
      logWarn("[ProductOptions]", "calculate returned error", { productId, reason, payload });
      return;
    }

    const priceData = payload?.price ?? payload;
    const formatted = priceData?.formatted
      || priceData?.PRICE_FORMATTED
      || priceData?.formattedPrice
      || priceData?.raw?.PRICE_FORMATTED
      || null;
    const fallbackText = formatted || (priceData?.raw && typeof priceData.raw.PRICE !== "undefined"
      ? String(priceData.raw.PRICE)
      : null);
    logDebug("[ProductOptions]", "response", { productId, reason, priceData });
    if (formatted || fallbackText) {
      applyPriceToContainer(productContainer, formatted || fallbackText, productId);
    } else {
      logWarn("[ProductOptions]", "formatted price missing", { productId, reason, priceData });
    }
  }).catch((error) => {
    logError("[ProductOptions]", "calculate request failed", error);
  });
}

function applyPriceToContainer(productContainer, formattedPrice, productId = null) {
  const decoded = decodeHtml(formattedPrice);
  const selectorList = [".main-details__price-new", ".main-cataloge__price", ".favorit-box__item-price"];
  const targets = new Set();

  if (productContainer instanceof Element) {
    selectorList.forEach((selector) => {
      const node = productContainer.querySelector(selector);
      if (node) {
        targets.add(node);
      }
    });
  }

  if (targets.size === 0 && productId) {
    selectorList.forEach((selector) => {
      document.querySelectorAll(`[data-fls-like-product="${productId}"] ${selector}`).forEach((node) => {
        targets.add(node);
      });
    });
  }

  if (targets.size === 0) {
    logWarn("[ProductOptions]", "applyPriceToContainer: price nodes not found", { formattedPrice, productId });
    return;
  }

  targets.forEach((node) => {
    if (node.classList && node.classList.contains("favorit-box__item-price")) {
      node.innerHTML = formattedPrice;
      return;
    }
    node.textContent = decoded;
  });
}

function getProductIdFromContainer(container) {
  let scope = container instanceof Element ? container : null;

  if (!scope) {
    scope = document.querySelector('.main-details, .main__details') || document.querySelector('.main-cataloge__item');
  }

  if (scope) {
    if (scope.dataset && scope.dataset.productId) {
      return parseInt(scope.dataset.productId, 10) || 0;
    }
    if (scope.dataset && scope.dataset.flsLikeProduct) {
      return parseInt(scope.dataset.flsLikeProduct, 10) || 0;
    }
    const button = scope.querySelector('[data-fls-like-button]');
    if (button) {
      const id = parseInt(button.dataset.productId || button.dataset.flsLikeProduct, 10) || 0;
      return id;
    }
  }

  const globalButton = document.querySelector('[data-fls-like-button]');
  if (globalButton) {
    const id = parseInt(globalButton.dataset.productId || globalButton.dataset.flsLikeProduct, 10) || 0;
    return id;
  }

  return 0;
}

function decodeHtml(html) {
  PRICE_DECODE_HELPER.innerHTML = html;
  return PRICE_DECODE_HELPER.textContent || '';
}
