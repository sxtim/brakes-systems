const PRICE_UPDATE_DELAY = 150;
const PRICE_DECODE_HELPER = document.createElement("span");
const pendingPriceTimers = new Map();
const PRODUCT_OPTIONS_ENABLED = window.__BRAKES_PRODUCT_OPTIONS_ENABLED__ !== false;
const OPTION_DEFAULTS = Object.freeze({
  two_piece_disc_construction: "no",
  rotor_pattern: "perforation",
  caliper_logo: "standard",
});
const LOG_ENABLED = false;
const BASKET_PATHS = ["/personal/cart", "/basket"];

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

function isBasketPage() {
  const path = window.location && window.location.pathname ? window.location.pathname : "";
  return BASKET_PATHS.some((prefix) => path === prefix || path.startsWith(`${prefix}/`));
}

function isBasketScope(node) {
  if (!(node instanceof Element)) {
    return false;
  }
  return Boolean(node.closest(".basket__products, .basket__item--card, .basket__body"));
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

function resolveOptionKey(featureTitle) {
  if (!featureTitle) {
    return null;
  }

  if (
    featureTitle.includes("Плавающая конструкция диска")
    || featureTitle.includes("Двусоставная конструкция диска")
  ) {
    return "two_piece_disc_construction";
  }
  if (featureTitle.includes("Тип ротора") || featureTitle.includes("Рисунок ротора")) {
    return "rotor_pattern";
  }
  if (featureTitle.includes("Лого на суппорт")) {
    return "caliper_logo";
  }

  return null;
}

function findOptionNode(optionItems, key, value) {
  const items = Array.from(optionItems || []);
  if (items.length === 0) {
    return null;
  }

  const valueLower = toLowerString(value);
  const pick = (predicate) => items.find((item) => {
    const text = toLowerString(item.textContent || "");
    return predicate(text);
  }) || null;

  if (key === "two_piece_disc_construction") {
    if (valueLower === "yes") {
      return pick((text) => text.includes("да")) || null;
    }
    return pick((text) => text.includes("нет")) || null;
  }

  if (key === "rotor_pattern") {
    if (valueLower === "perforation_slots" || valueLower === "perforation_and_notches") {
      return pick((text) => text.includes("перф") && text.includes("насеч"));
    }
    if (valueLower === "perforation") {
      return pick((text) => text.includes("перф") && !text.includes("насеч")) || pick((text) => text.includes("перф"));
    }
    if (valueLower === "slots" || valueLower === "notches") {
      return pick((text) => text.includes("насеч"));
    }
    if (valueLower === "none") {
      return pick((text) => text.includes("нет"));
    }
  }

  if (key === "caliper_logo") {
    if (valueLower === "special" || valueLower === "custom_logo" || valueLower === "custom") {
      return pick((text) => text.includes("особ") || text.includes("логотип"));
    }
    return pick((text) => text.includes("стандарт") || text.includes("станд"));
  }

  return null;
}

function applySelectedOptions(scope, optionsPayload) {
  const normalized = normalizeOptionsPayload(optionsPayload);
  if (!normalized || !normalized.options) {
    return false;
  }

  const options = { ...OPTION_DEFAULTS, ...normalized.options };
  const root = scope instanceof Element ? scope : document;
  let changed = false;

  root.querySelectorAll(".spollers__item").forEach((spoller) => {
    const optionItems = spoller.querySelectorAll(".main-cataloge__sublist-item");
    if (optionItems.length === 0) {
      return;
    }

    const featureTitleElement = spoller.querySelector(".main-cataloge__feature-item, .main-details__feature-item");
    if (!featureTitleElement) {
      return;
    }

    const featureTitle = featureTitleElement.textContent.trim();
    const key = resolveOptionKey(featureTitle);
    if (!key || typeof options[key] === "undefined") {
      return;
    }

    const target = findOptionNode(optionItems, key, options[key]);
    if (!target) {
      return;
    }

    optionItems.forEach((item) => item.classList.remove("selected"));
    target.classList.add("selected");
    changed = true;
  });

  return changed;
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
    if (normalizedKey === "electric_handbrake") {
      return;
    }
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

function buildFavoriteKey(productId, context = null) {
  const id = parseInt(productId, 10);
  if (!id) {
    return "";
  }

  const sectionId = parseInt(context?.section_id || context?.sectionId || context?.section, 10);
  let sectionPath = context?.section_path || context?.sectionPath || "";
  if (typeof sectionPath === "string") {
    sectionPath = sectionPath.trim().replace(/^\/+|\/+$/g, "");
  } else {
    sectionPath = "";
  }

  if (sectionId > 0) {
    return `${id}:s${sectionId}`;
  }

  if (sectionPath) {
    return `${id}:p${sectionPath}`;
  }

  return `${id}:n`;
}

function extractContextFromContainer(container) {
  if (!(container instanceof Element)) {
    return null;
  }

  const dataset = container.dataset || {};
  let sectionId = parseInt(dataset.contextSectionId || 0, 10);
  let sectionPath = dataset.contextPath ? String(dataset.contextPath) : "";

  if (!sectionId && !sectionPath) {
    const fallback = container.querySelector("[data-context-section-id], [data-context-path]");
    if (fallback && fallback.dataset) {
      sectionId = parseInt(fallback.dataset.contextSectionId || 0, 10);
      sectionPath = fallback.dataset.contextPath ? String(fallback.dataset.contextPath) : sectionPath;
    }
  }

  const context = {};
  if (sectionId > 0) {
    context.section_id = sectionId;
  }
  if (sectionPath && sectionPath.trim() !== "") {
    context.section_path = sectionPath.trim();
  }

  return Object.keys(context).length > 0 ? context : null;
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

function extractPriceFromMeta(productId, context = null) {
  const meta = window.__FAVORITES__?.meta;
  const metaByProduct = window.__FAVORITES__?.metaByProduct;
  if ((!meta || typeof meta !== "object") && (!metaByProduct || typeof metaByProduct !== "object")) {
    return null;
  }

  const favoriteKey = buildFavoriteKey(productId, context);
  const entry = (favoriteKey && meta && meta[favoriteKey]) || (metaByProduct && metaByProduct[productId]);
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
  if (!PRODUCT_OPTIONS_ENABLED) {
    return;
  }

  const spoller = event.target.closest(".spollers__item");
  const optionNode = event.target.closest(".main-cataloge__sublist-item");
  if (!spoller || !optionNode) {
    return;
  }

  if (isBasketPage()) {
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
  if (!PRODUCT_OPTIONS_ENABLED) {
    handleOneClickBuyButtons();
    return;
  }

  if (isBasketPage()) {
    handleOneClickBuyButtons();
    return;
  }

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

function handleOneClickBuyButtons(root = document) {
  const hasDocumentFragment = typeof DocumentFragment !== "undefined";
  let scope = document;

  if (root instanceof Document) {
    scope = root;
  } else if (root instanceof Element) {
    scope = root;
  } else if (hasDocumentFragment && root instanceof DocumentFragment && typeof root.querySelectorAll === "function") {
    scope = root;
  }

  scope.querySelectorAll('[data-fls-popup-link="speedBuy"]').forEach((button) => {
    if (button.dataset.oneClickBound === "true") {
      return;
    }

    button.dataset.oneClickBound = "true";

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
        two_piece_disc_construction: "Плавающая конструкция диска",
        rotor_pattern: "Тип ротора",
        caliper_logo: "Лого на суппорт",
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

      const popupInstance = window.flsPopup;
      if (popupInstance && typeof popupInstance.open === "function") {
        popupInstance.open("speedBuy");
      }
    });
  });
}

document.addEventListener("favorites:popupHtmlUpdated", (event) => {
  const container = event?.detail?.container;
  const isFragment = typeof DocumentFragment !== "undefined" && container instanceof DocumentFragment;
  if (container instanceof Element || isFragment) {
    handleOneClickBuyButtons(container);
    return;
  }

  handleOneClickBuyButtons();
});

function updateProductOptions(productContainer, reason = "manual") {
  if (!PRODUCT_OPTIONS_ENABLED) {
    return {
      productId: 0,
      favoriteKey: "",
      context: null,
      options: {},
      reason,
      timestamp: Date.now(),
    };
  }

  if (isBasketPage()) {
    return {
      productId: 0,
      favoriteKey: "",
      context: null,
      options: {},
      reason,
      timestamp: Date.now(),
    };
  }

  const scope = productContainer instanceof Element ? productContainer : document;
  const productId = getProductIdFromContainer(scope);
  const context = extractContextFromContainer(scope);
  const favoriteKey = buildFavoriteKey(productId, context);
  const fallbackOptions = getInitialOptions(scope, productId, context);
  const fallbackClone = cloneOptionsPayload(fallbackOptions);

  let optionsPayload = null;
  if ((reason === "init-card" || reason === "init-details") && fallbackClone) {
    applySelectedOptions(scope, fallbackClone);
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
        favoriteKey,
        context,
        options: normalizedOptions,
        reason,
      },
    }));
  }

  return {
    productId,
    favoriteKey,
    context,
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
    const optionItems = spoller.querySelectorAll(".main-cataloge__sublist-item");
    if (optionItems.length === 0) {
      return;
    }

    let selectedOption = spoller.querySelector(".main-cataloge__sublist-item.selected");
    if (!selectedOption) {
      selectedOption = optionItems[0];
      if (selectedOption) {
        selectedOption.classList.add("selected");
      }
    }

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

    if (
      featureTitle.includes("Плавающая конструкция диска")
      || featureTitle.includes("Двусоставная конструкция диска")
    ) {
      englishKey = "two_piece_disc_construction";
      englishValue = optionText.toLowerCase() === "да" ? "yes" : "no";
    } else if (featureTitle.includes("Тип ротора") || featureTitle.includes("Рисунок ротора")) {
      englishKey = "rotor_pattern";
      if (optionText.toUpperCase().includes("ПЕРФОРАЦИЯ") && optionText.toUpperCase().includes("НАСЕЧКИ")) {
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

    // Options change means a different configuration; reset "in basket" state
    // so user can add the new config even if previous one was already in basket.
    if (button.classList) {
      button.classList.remove("is-in-basket");
    }
    const textNode = button.querySelector(".main-details__shoping-text, .main-cataloge__shoping-text");
    if (textNode) {
      textNode.textContent = "В корзину";
    }
  });

  favoriteButtons.forEach((button) => {
    button.setAttribute("data-options", value);
  });
}

function getInitialOptions(scope, productId, context = null) {
  const datasetPayload = extractOptionsFromDataset(scope, productId);
  const metaPayload = productId ? extractOptionsFromMeta(productId, context) : null;

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

function extractOptionsFromMeta(productId, context = null) {
  const meta = window.__FAVORITES__?.meta;
  const metaByProduct = window.__FAVORITES__?.metaByProduct;
  if ((!meta || typeof meta !== "object") && (!metaByProduct || typeof metaByProduct !== "object")) {
    return null;
  }

  const favoriteKey = buildFavoriteKey(productId, context);
  const entry = (favoriteKey && meta && meta[favoriteKey]) || (metaByProduct && metaByProduct[productId]);
  if (!entry || typeof entry !== "object") {
    return null;
  }

  return normalizeOptionsPayload(entry.options || entry);
}

function schedulePriceUpdate(productContainer, optionsData, reason = "manual") {
  if (!PRODUCT_OPTIONS_ENABLED) {
    return;
  }

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

  if (isBasketPage() || isBasketScope(targetContainer)) {
    return;
  }

  const context = optionsData?.context || extractContextFromContainer(targetContainer);
  const favoriteKey = buildFavoriteKey(productId, context);

  if ((reason === "init-card" || reason === "init-details")) {
    const metaPrice = extractPriceFromMeta(productId, context);
    if (metaPrice !== null && metaPrice !== undefined) {
      applyPriceToContainer(targetContainer, metaPrice, productId);
      return;
    }
  }

  const key = favoriteKey || String(productId);
  if (pendingPriceTimers.has(key)) {
    clearTimeout(pendingPriceTimers.get(key));
  }

  const payload = {
    options: { ...(optionsData?.options || {}) },
    context: context || null,
  };

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
