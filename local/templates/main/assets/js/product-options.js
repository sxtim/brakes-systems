const PRICE_UPDATE_DELAY = 150;
const PRICE_DECODE_HELPER = document.createElement("span");
const pendingPriceTimers = new Map();

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
          const optionsData = JSON.parse(options);
          const optionsArray = [];
          if (optionsData && typeof optionsData.options === "object") {
            for (const key in optionsData.options) {
              const rawValue = optionsData.options[key].value;
              if (keyMap[key] && rawValue) {
                const translatedValue = valueMap[rawValue.toLowerCase()] || rawValue;
                optionsArray.push(`${keyMap[key]}: ${translatedValue}`);
              }
            }
          }
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

  let optionsData = null;
  if (reason === "init-card" && fallbackClone) {
    optionsData = fallbackClone;
  } else {
    optionsData = collectSelectedOptions(scope, fallbackOptions, reason);
  }

  const optionsJson = JSON.stringify(optionsData);

  setOptionsAttribute(scope, optionsJson);

  if (productId) {
    document.dispatchEvent(new CustomEvent("productOptions:changed", {
      detail: {
        productId,
        options: optionsData,
        reason,
      },
    }));
    optionsData.productId = productId;
  }

  return optionsData;
}

function collectSelectedOptions(scope, defaults = null, reason = "manual") {
  const baseDefaults = {
    two_piece_disc_construction: { value: "no" },
    rotor_pattern: { value: "none" },
    caliper_logo: { value: "standard" },
    electric_handbrake: { value: "no" },
  };
  const selectedOptions = { ...baseDefaults };
  if (defaults && defaults.options && typeof defaults.options === "object") {
    Object.keys(defaults.options).forEach((key) => {
      const value = defaults.options[key];
      if (value && typeof value === "object") {
        selectedOptions[key] = { ...value };
      }
    });
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

    selectedOptions[englishKey] = {
      value: englishValue,
    };
  });

  return { options: selectedOptions };
}

function setOptionsAttribute(scope, payload) {
  const holder = scope instanceof Element ? scope : document;
  if (!holder) {
    return;
  }

  let value = payload;
  if (value && typeof value === "object") {
    try {
      value = JSON.stringify(value);
    } catch (error) {
      value = null;
    }
  }

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
  const result = {};

  const optionsFromDataset = extractOptionsFromDataset(scope, productId);
  if (optionsFromDataset) {
    Object.assign(result, optionsFromDataset);
  }

  if (productId) {
    const metaOptions = extractOptionsFromMeta(productId);
    if (metaOptions) {
      Object.assign(result, metaOptions);
      if (!optionsFromDataset) {
        setOptionsAttribute(scope, JSON.stringify({ options: metaOptions.options || metaOptions }));
      }
    }
  }

  return result;
}

function cloneOptionsPayload(input) {
  if (!input || typeof input !== "object") {
    return null;
  }

  const source = input.options && typeof input.options === "object"
    ? input.options
    : input;

  if (!source || typeof source !== "object") {
    return null;
  }

  const normalized = {};
  Object.keys(source).forEach((key) => {
    const raw = source[key];
    if (raw && typeof raw === "object") {
      normalized[key] = { ...raw };
    } else if (raw !== undefined && raw !== null) {
      normalized[key] = { value: raw };
    }
  });

  if (Object.keys(normalized).length === 0) {
    return null;
  }

  return { options: normalized };
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

  try {
    const parsed = JSON.parse(button.dataset.options);
    if (parsed && typeof parsed === "object") {
      return parsed;
    }
  } catch (error) {
  }

  return null;
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

  return entry.options || null;
}

function schedulePriceUpdate(productContainer, optionsData, reason = "manual") {
  let targetContainer = productContainer instanceof Element ? productContainer : null;
  if (!targetContainer && optionsData && optionsData.options) {
    const lookupId = typeof optionsData.productId !== "undefined" ? optionsData.productId : null;
    if (lookupId) {
      targetContainer = document.querySelector(`[data-fls-like-product="${lookupId}"]`);
    }
  }

  const productId = getProductIdFromContainer(targetContainer || productContainer);
  if (!productId) {
    return;
 }

  if (!targetContainer) {
    targetContainer = document.querySelector(`[data-fls-like-product="${productId}"]`)
      || document.querySelector(`[data-product-id="${productId}"]`)
      || document.querySelector(".main-details, .main__details")
      || document.querySelector(".main-cataloge__item")
      || document;
  }

  if (!targetContainer) {
  }


  const key = String(productId);
  if (pendingPriceTimers.has(key)) {
    clearTimeout(pendingPriceTimers.get(key));
  }

  const timer = setTimeout(() => {
    pendingPriceTimers.delete(key);
    requestPriceUpdate(targetContainer, productId, optionsData, reason);
  }, PRICE_UPDATE_DELAY);

  pendingPriceTimers.set(key, timer);
}

function requestPriceUpdate(productContainer, productId, optionsData, reason = "manual") {
  if (typeof BX === "undefined" || !BX.ajax || typeof BX.ajax.runComponentAction !== "function") {
    return;
  }


  BX.ajax.runComponentAction("brakes:favorites.sync", "calculate", {
    mode: "class",
    data: { productId, options: optionsData },
  }).then((response) => {
    const priceData = response?.data?.price || response?.data;
    const formatted = priceData?.formatted || priceData?.PRICE_FORMATTED || priceData?.formattedPrice || null;
    if (formatted) {
      applyPriceToContainer(productContainer, formatted);
    }
  }).catch((error) => {
  });
}

function applyPriceToContainer(productContainer, formattedPrice) {
  const decoded = decodeHtml(formattedPrice);
  const targets = [];

  if (productContainer instanceof Element) {
    const node = productContainer.querySelector(".main-details__price-new, .main-cataloge__price");
    if (node) {
      targets.push(node);
    }
  }

  if (targets.length === 0) {
    const detailNode = document.querySelector(".main-details__price-new");
    if (detailNode) {
      targets.push(detailNode);
    }
  }


  targets.forEach((node) => {
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
