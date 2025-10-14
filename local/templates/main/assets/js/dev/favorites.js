const FAVORITE_BUTTON_SELECTOR = "[data-fls-like-button]";
const FAVORITE_PRODUCT_SELECTOR = "[data-fls-like-product]";
const FAVORITE_COUNTER_SELECTOR = "[data-fls-like]";
const ACTIVE_CLASS = "liked";
const FAVORITE_POPUP_BODY_SELECTOR = ".favorit-box__body";
const PRICE_SELECTORS = [".main-details__price-new", ".main-cataloge__price"];

function favoritesLog(...args) {
    if (typeof console !== "undefined" && console.log) {
        console.log("[Favorites]", ...args);
    }
}

const initialState = window.__FAVORITES__ || { items: [], count: 0, isAuthorized: false, meta: {} };
const state = {
    items: normalizeIds(initialState.items || []),
    isAuthorized: Boolean(initialState.isAuthorized),
    meta: typeof initialState.meta === "object" && initialState.meta !== null ? initialState.meta : {},
};

const priceObservers = new WeakMap();
const lastPriceHtml = new WeakMap();
const pendingUpdateTimers = new Map();

function applyPriceToPopup(id, priceHtml, priceText) {
    const itemNode = document.querySelector(`[data-fls-like-product="${id}"]`);
    if (!itemNode) {
        favoritesLog("applyPriceToPopup skip: item missing", { id });
        return;
    }

    const priceNode = itemNode.querySelector('.favorit-box__item-price');
    if (!priceNode) {
        favoritesLog("applyPriceToPopup skip: price node missing", { id });
        return;
    }

    favoritesLog("applyPriceToPopup", {
        id,
        priceHtml,
        priceText,
        previous: priceNode.innerHTML,
    });

    if (typeof priceHtml === 'string' && priceHtml !== '') {
        priceNode.innerHTML = priceHtml;
    } else if (typeof priceText === 'string') {
        priceNode.textContent = priceText;
    }
}

function normalizeIds(ids) {
    const map = {};
    (ids || []).forEach((value) => {
        const id = parseInt(value, 10);
        if (id > 0) {
            map[id] = true;
        }
    });

    const result = Object.keys(map).map((key) => parseInt(key, 10));
    result.sort((a, b) => a - b);
    return result;
}

function getProductId(element) {
    if (!element) {
        return 0;
    }

    if (element.dataset.productId) {
        return parseInt(element.dataset.productId, 10) || 0;
    }

    const productNode = element.closest(FAVORITE_PRODUCT_SELECTOR);
    if (productNode && productNode.dataset.productId) {
        return parseInt(productNode.dataset.productId, 10) || 0;
    }

    if (productNode && productNode.dataset.flsLikeProduct) {
        return parseInt(productNode.dataset.flsLikeProduct, 10) || 0;
    }

    return 0;
}

function updateCounter() {
    const counter = document.querySelector(FAVORITE_COUNTER_SELECTOR);
    if (counter) {
        counter.textContent = String(state.items.length);
    }
}

function updateButtons() {
    const map = state.items.reduce((acc, id) => {
        acc[id] = true;
        return acc;
    }, {});

    document.querySelectorAll(FAVORITE_BUTTON_SELECTOR).forEach((button) => {
        const productId = getProductId(button);
        const isActive = !!map[productId];
        button.classList.toggle(ACTIVE_CLASS, isActive);
        button.setAttribute("aria-pressed", isActive ? "true" : "false");
    });
}

function updatePopupHtml(markup) {
    if (typeof markup === "string") {
        const container = document.querySelector(FAVORITE_POPUP_BODY_SELECTOR);
        if (container) {
            favoritesLog("Update popup HTML (string)", { length: markup.length });
            container.innerHTML = markup;
            initPriceObservers(container);
        }
        return;
    }

    if (!markup || typeof markup !== "object") {
        return;
    }

    const items = Array.isArray(markup.items) ? markup.items : [];
    items.forEach((item) => {
        const id = parseInt(item.id, 10);
        if (!id) {
            favoritesLog("Skip popup update item without id", item);
            return;
        }

        favoritesLog("Update popup HTML (partial)", id, item);
        applyPriceToPopup(id, item.priceHtml ?? null, item.price ?? null);
    });
}

function getContainerProductId(container) {
    if (!container) {
        return 0;
    }

    if (container.dataset.productId) {
        return parseInt(container.dataset.productId, 10) || 0;
    }

    if (container.dataset.flsLikeProduct) {
        return parseInt(container.dataset.flsLikeProduct, 10) || 0;
    }

    return 0;
}

function getPriceElement(container) {
    if (!container) {
        return null;
    }

    const roots = [container, container.closest('.main-cataloge__item'), container.closest('.main-details')];
    for (const root of roots) {
        if (!root) {
            continue;
        }

        for (const selector of PRICE_SELECTORS) {
            const node = root.querySelector(selector);
            if (node) {
                return node;
            }
        }
    }

    return null;
}

function emitPriceUpdate(id, priceElement) {
    if (!priceElement) {
        return;
    }

    const priceHtml = priceElement.innerHTML;
    if (lastPriceHtml.get(priceElement) === priceHtml) {
        return;
    }

    lastPriceHtml.set(priceElement, priceHtml);

    const priceText = priceElement.textContent.trim();
    applyPriceToPopup(id, priceHtml, priceText);
}

function observeProductPrice(container) {
    if (!container || priceObservers.has(container)) {
        return;
    }

    const id = getContainerProductId(container);
    if (!id) {
        return;
    }

    const priceElement = getPriceElement(container);
    if (!priceElement) {
        return;
    }

    const observer = new MutationObserver(() => {
        emitPriceUpdate(id, priceElement);
    });

    observer.observe(priceElement, { characterData: true, childList: true, subtree: true });
    priceObservers.set(container, observer);

    emitPriceUpdate(id, priceElement);
}

function initPriceObservers(root = document) {
    root.querySelectorAll(FAVORITE_PRODUCT_SELECTOR).forEach(observeProductPrice);
}

const productMutationObserver = typeof MutationObserver === "undefined" ? null : new MutationObserver((mutations) => {
    if (!Array.isArray(mutations)) {
        return;
    }

    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (!(node instanceof Element)) {
                return;
            }

            if (node.matches && node.matches(FAVORITE_PRODUCT_SELECTOR)) {
                observeProductPrice(node);
            }

            if (node.querySelectorAll) {
                node.querySelectorAll(FAVORITE_PRODUCT_SELECTOR).forEach(observeProductPrice);
            }
        });
    });
});

function applyState(items, popupHtml = null, meta = null) {
    favoritesLog("Apply state called", { items, popupHtml, meta });
    state.items = normalizeIds(items);
    const previousMeta = state.meta || {};
    if (meta && typeof meta === "object") {
        state.meta = meta;
        favoritesLog("State meta replaced", meta);
    } else {
        const filtered = {};
        state.items.forEach((id) => {
            if (previousMeta[id]) {
                filtered[id] = previousMeta[id];
            }
        });
        state.meta = filtered;
        favoritesLog("State meta filtered", filtered);
    }
    updateCounter();
    updateButtons();

    if (popupHtml !== null && popupHtml !== undefined) {
        updatePopupHtml(popupHtml);
    }

    document.dispatchEvent(new CustomEvent("favorites:changed", {
        detail: {
            items: [...state.items],
            count: state.items.length,
            isAuthorized: state.isAuthorized,
            meta: state.meta,
        },
    }));
    favoritesLog("State applied", state);
}

function handleButtonClick(event) {
    const target = event.target.closest(FAVORITE_BUTTON_SELECTOR);
    if (!target) {
        return;
    }

    const productId = getProductId(target);
    if (!productId) {
        favoritesLog("Button click without product id", target);
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    const priceSnapshot = captureCurrentPrice(target.closest(FAVORITE_PRODUCT_SELECTOR));
    const optionsData = extractOptionsData(target, productId);
    favoritesLog("Toggle click", { productId, priceSnapshot, optionsData });

    toggleFavorite(productId, target, priceSnapshot, optionsData);
}

function captureCurrentPrice(container) {
    const priceElement = getPriceElement(container);
    if (!priceElement) {
        return null;
    }

    return {
        priceHtml: priceElement.innerHTML,
        priceText: priceElement.textContent.trim(),
    };
}

function toggleFavorite(productId, button, priceSnapshot, optionsOverride = null) {
    if (typeof BX === "undefined" || !BX.ajax || typeof BX.ajax.runComponentAction !== "function") {
        console.error("Favorites: Bitrix ajax is not available.");
        favoritesLog("BX.ajax is not available");
        return;
    }

    button?.classList.add("is-processing");

    const options = optionsOverride ?? extractOptionsData(button, productId);
    favoritesLog("Toggle request", { productId, options, priceSnapshot });

    BX.ajax.runComponentAction("brakes:favorites.sync", "toggle", {
        mode: "class",
        data: { productId, options },
    }).then((response) => {
        favoritesLog("Toggle response", response);
        const data = response?.data;
        favoritesLog("Toggle response data", data, Array.isArray(data?.items));
        if (!data || data.status !== "success" || !Array.isArray(data.items)) {
            throw new Error("Unexpected response format");
        }

        applyState(data.items, data.popupHtml, data.meta);

        button?.classList.remove("is-processing");
    }).catch((error) => {
        console.error("Favorites: toggle failed", error);
        favoritesLog("Toggle failed", error);
        if (BX?.UI?.Notification?.Center) {
            BX.UI.Notification.Center.notify({
                content: BX.message?.ERROR_FAVORITES_TOGGLE || "Error updating favorites.",
                autoHideDelay: 5000,
                position: "top-right",
            });
        }
        button?.classList.remove("is-processing");
    });
}


function refreshFromServer() {
    favoritesLog("refreshFromServer invoked");
    if (typeof BX === "undefined" || !BX.ajax || typeof BX.ajax.runComponentAction !== "function") {
        return Promise.resolve(state.items);
    }

    return BX.ajax.runComponentAction("brakes:favorites.sync", "list", {
        mode: "class",
    }).then((response) => {
        favoritesLog("refreshFromServer response", response);
        const data = response?.data;
        if (!data || data.status !== "success" || !Array.isArray(data.items)) {
            throw new Error("Unexpected response format");
        }

        applyState(data.items, data.popupHtml, data.meta);

        return state.items;
    }).catch((error) => {
        console.error("Favorites: refresh failed", error);
        favoritesLog("refreshFromServer error", error);
        return state.items;
    });
}

function initFavorites() {
    favoritesLog("initFavorites start", state);
    applyState(state.items);
    initPriceObservers();

    if (productMutationObserver) {
        productMutationObserver.observe(document.body, { childList: true, subtree: true });
    }

    document.addEventListener("click", handleButtonClick, true);
    document.addEventListener("productOptions:changed", handleProductOptionsChanged);
    favoritesLog("initFavorites completed");
}

window.addEventListener("load", initFavorites);

window.brakesFavorites = {
    getIds: () => [...state.items],
    has: (id) => state.items.includes(parseInt(id, 10)),
    toggle: (id, options) => toggleFavorite(parseInt(id, 10), null, null, options || null),
    refresh: refreshFromServer,
};

function parseOptions(value) {
    favoritesLog("parseOptions input", value);
    if (!value) {
        return null;
    }

    if (typeof value === "object") {
        return value;
    }

    if (typeof value === "string") {
        try {
            const parsed = JSON.parse(value);
            favoritesLog("parseOptions parsed", parsed);
            return parsed;
        } catch (error) {
            console.warn("Favorites: failed to parse options JSON", error);
            favoritesLog("parseOptions failed", error);
        }
    }

    return null;
}

function normalizeOptionsPayload(options) {
    favoritesLog("normalizeOptionsPayload input", options);
    const parsed = parseOptions(options);
    if (parsed && typeof parsed === "object") {
        return parsed;
    }
    return {};
}

function extractOptionsData(element, productId) {
    favoritesLog("extractOptionsData input", { element, productId });
    if (element) {
        const direct = normalizeOptionsPayload(element.dataset?.options);
        if (Object.keys(direct).length > 0) {
            favoritesLog("extractOptionsData direct dataset", direct);
            return direct;
        }

        const container = element.closest(FAVORITE_PRODUCT_SELECTOR)
            || element.closest(".main-cataloge__item")
            || element.closest(".main-details")
            || element.closest(".main__details");

        if (container) {
            const button = container.querySelector("[data-fls-like-button]");
            if (button && button !== element) {
                const parsed = normalizeOptionsPayload(button.dataset?.options);
                if (Object.keys(parsed).length > 0) {
                    favoritesLog("extractOptionsData sibling dataset", parsed);
                    return parsed;
                }
            }
        }
    }

    favoritesLog("extractOptionsData dataset missing", { productId });
    if (productId && state.meta && state.meta[productId] && state.meta[productId].options) {
        favoritesLog("extractOptionsData from meta", state.meta[productId].options);
        return state.meta[productId].options;
    }

    favoritesLog("extractOptionsData empty result");
    return {};
}

function handleProductOptionsChanged(event) {
    favoritesLog("handleProductOptionsChanged", event);
    const detail = event?.detail || {};
    const productId = parseInt(detail.productId, 10);
    if (!productId || !state.items.includes(productId)) {
        return;
    }

    const options = normalizeOptionsPayload(detail.options);
    favoritesLog("handleProductOptionsChanged processed", { productId, options });
    scheduleFavoriteUpdate(productId, options);
}

function scheduleFavoriteUpdate(productId, options) {
    favoritesLog("scheduleFavoriteUpdate", { productId, options });
    const key = String(productId);
    if (pendingUpdateTimers.has(key)) {
        clearTimeout(pendingUpdateTimers.get(key));
    }

    const timer = setTimeout(() => {
        favoritesLog("scheduleFavoriteUpdate trigger", { productId, options });
        pendingUpdateTimers.delete(key);
        sendUpdateRequest(productId, options);
    }, 200);

    pendingUpdateTimers.set(key, timer);
}

function sendUpdateRequest(productId, options) {
    favoritesLog("sendUpdateRequest", { productId, options });
    if (typeof BX === "undefined" || !BX.ajax || typeof BX.ajax.runComponentAction !== "function") {
        favoritesLog("sendUpdateRequest skipped: BX.ajax not available");
        return;
    }

    BX.ajax.runComponentAction("brakes:favorites.sync", "update", {
        mode: "class",
        data: { productId, options },
    }).then((response) => {
        favoritesLog("sendUpdateRequest response", response);
        const data = response?.data;
        if (!data || data.status !== "success" || !Array.isArray(data.items)) {
            throw new Error("Unexpected response format");
        }

        applyState(data.items, data.popupHtml, data.meta);
    }).catch((error) => {
        console.error("Favorites: update failed", error);
        favoritesLog("sendUpdateRequest error", error);
    });
}
