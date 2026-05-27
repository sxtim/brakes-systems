const FAVORITE_BUTTON_SELECTOR = "[data-fls-like-button]";
const FAVORITE_PRODUCT_SELECTOR = "[data-fls-like-product]";
const FAVORITE_COUNTER_SELECTOR = "[data-fls-like]";
const ACTIVE_CLASS = "liked";
const FAVORITE_POPUP_BODY_SELECTOR = ".favorit-box__body";
const PRICE_SELECTORS = [".main-details__price-new", ".main-cataloge__price"];


const initialState = window.__FAVORITES__ || { items: [], count: 0, isAuthorized: false, meta: {}, metaByProduct: {} };
const state = {
    items: normalizeKeys(initialState.items || []),
    isAuthorized: Boolean(initialState.isAuthorized),
    meta: typeof initialState.meta === "object" && initialState.meta !== null ? initialState.meta : {},
    metaByProduct: typeof initialState.metaByProduct === "object" && initialState.metaByProduct !== null
        ? initialState.metaByProduct
        : {},
};

const priceObservers = new WeakMap();
const lastPriceHtml = new WeakMap();
const pendingUpdateTimers = new Map();

function applyPriceToPopup(targetKey, priceHtml, priceText) {
    const key = typeof targetKey === "string" ? targetKey : "";
    const nodes = key
        ? Array.from(document.querySelectorAll(`[data-favorite-key="${key}"]`))
        : [];

    const fallbackId = !key && typeof targetKey === "number" ? targetKey : parseInt(targetKey, 10) || 0;
    if (nodes.length === 0 && fallbackId) {
        nodes.push(...document.querySelectorAll(`[data-fls-like-product="${fallbackId}"]`));
    }

    if (nodes.length === 0) {
        return;
    }

    nodes.forEach((itemNode) => {
        const priceNode = itemNode.querySelector('.favorit-box__item-price');
        if (!priceNode) {
            return;
        }

        if (typeof priceHtml === 'string' && priceHtml !== '') {
            priceNode.innerHTML = priceHtml;
        } else if (typeof priceText === 'string') {
            priceNode.textContent = priceText;
        }
    });
}

function buildFavoriteKey(productId, context = null) {
    const id = parseInt(productId, 10);
    if (!id) {
        return "";
    }

    const sectionId = parseInt(context?.section_id || context?.sectionId || 0, 10);
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

function parseFavoriteKey(value) {
    if (typeof value !== "string" || !value) {
        return null;
    }

    const match = value.match(/^(\d+):(s|p|n)(.*)$/);
    if (match) {
        const productId = parseInt(match[1], 10);
        const type = match[2];
        const tail = match[3] || "";
        const context = {};
        if (type === "s") {
            const sectionId = parseInt(tail, 10);
            if (sectionId > 0) {
                context.section_id = sectionId;
            }
        } else if (type === "p") {
            const sectionPath = String(tail).replace(/^\/+|\/+$/g, "");
            if (sectionPath) {
                context.section_path = sectionPath;
            }
        }
        return { productId, context };
    }

    const fallbackId = parseInt(value, 10);
    return fallbackId > 0 ? { productId: fallbackId, context: null } : null;
}

function normalizeKeys(items) {
    const result = [];
    const seen = {};

    (items || []).forEach((value) => {
        let key = "";

        if (typeof value === "string") {
            if (value.includes(":")) {
                key = value;
            } else {
                const id = parseInt(value, 10);
                if (id > 0) {
                    key = buildFavoriteKey(id, null);
                }
            }
        } else {
            const id = parseInt(value, 10);
            if (id > 0) {
                key = buildFavoriteKey(id, null);
            }
        }

        if (!key || seen[key]) {
            return;
        }

        seen[key] = true;
        result.push(key);
    });

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

function getFavoriteKeyFromElement(element) {
    if (!element) {
        return "";
    }

    if (element.dataset && element.dataset.favoriteKey) {
        return element.dataset.favoriteKey;
    }

    const productId = getProductId(element);
    if (!productId) {
        return "";
    }

    const context = extractContext(element);
    const key = buildFavoriteKey(productId, context);
    if (!key) {
        return "";
    }

    if (element.dataset) {
        element.dataset.favoriteKey = key;
    }

    const container = element.closest(FAVORITE_PRODUCT_SELECTOR);
    if (container && container.dataset && !container.dataset.favoriteKey) {
        container.dataset.favoriteKey = key;
    }

    return key;
}

function updateCounter() {
    const counter = document.querySelector(FAVORITE_COUNTER_SELECTOR);
    if (counter) {
        counter.textContent = String(state.items.length);
    }
}

function updateButtons() {
    const map = state.items.reduce((acc, key) => {
        acc[key] = true;
        return acc;
    }, {});

    document.querySelectorAll(FAVORITE_BUTTON_SELECTOR).forEach((button) => {
        const favoriteKey = getFavoriteKeyFromElement(button);
        const isActive = favoriteKey ? !!map[favoriteKey] : false;
        button.classList.toggle(ACTIVE_CLASS, isActive);
        button.setAttribute("aria-pressed", isActive ? "true" : "false");
    });
}

function updatePopupHtml(markup) {
    if (typeof markup === "string") {
        const container = document.querySelector(FAVORITE_POPUP_BODY_SELECTOR);
        if (container) {
            container.innerHTML = markup;
            initPriceObservers(container);
            updateButtons();
            document.dispatchEvent(new CustomEvent("favorites:popupHtmlUpdated", {
                detail: { container },
            }));
        }
        return;
    }

    if (!markup || typeof markup !== "object") {
        return;
    }

    const items = Array.isArray(markup.items) ? markup.items : [];
    items.forEach((item) => {
        const key = typeof item.key === "string" ? item.key : (typeof item.favoriteKey === "string" ? item.favoriteKey : "");
        const id = parseInt(item.id, 10);
        if (!key && !id) {
            return;
        }

        applyPriceToPopup(key || id, item.priceHtml ?? null, item.price ?? null);
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

function getContainerFavoriteKey(container) {
    if (!container) {
        return "";
    }

    return getFavoriteKeyFromElement(container);
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

function emitPriceUpdate(key, priceElement) {
    if (!priceElement) {
        return;
    }

    const priceHtml = priceElement.innerHTML;
    if (lastPriceHtml.get(priceElement) === priceHtml) {
        return;
    }

    lastPriceHtml.set(priceElement, priceHtml);

    const priceText = priceElement.textContent.trim();
    applyPriceToPopup(key, priceHtml, priceText);
}

function observeProductPrice(container) {
    if (!container || priceObservers.has(container)) {
        return;
    }

    const key = getContainerFavoriteKey(container);
    if (!key) {
        return;
    }

    const priceElement = getPriceElement(container);
    if (!priceElement) {
        return;
    }

    const observer = new MutationObserver(() => {
        emitPriceUpdate(key, priceElement);
    });

    observer.observe(priceElement, { characterData: true, childList: true, subtree: true });
    priceObservers.set(container, observer);

    emitPriceUpdate(key, priceElement);
}

function initPriceObservers(root = document) {
    root.querySelectorAll(FAVORITE_PRODUCT_SELECTOR).forEach(observeProductPrice);
}

const productMutationObserver = typeof MutationObserver === "undefined" ? null : new MutationObserver((mutations) => {
    if (!Array.isArray(mutations)) {
        return;
    }

    let shouldRefreshButtons = false;

    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (!(node instanceof Element)) {
                return;
            }

            if (node.matches && node.matches(FAVORITE_PRODUCT_SELECTOR)) {
                observeProductPrice(node);
                document.dispatchEvent(new CustomEvent("favorites:popupHtmlUpdated", {
                    detail: { container: node },
                }));
            }

            if (node.querySelectorAll) {
                node.querySelectorAll(FAVORITE_PRODUCT_SELECTOR).forEach((productNode) => {
                    observeProductPrice(productNode);
                    document.dispatchEvent(new CustomEvent("favorites:popupHtmlUpdated", {
                        detail: { container: productNode },
                    }));
                    shouldRefreshButtons = true;
                });
            }

            if (node.matches && node.matches(FAVORITE_BUTTON_SELECTOR)) {
                shouldRefreshButtons = true;
            }
        });
    });

    if (shouldRefreshButtons) {
        updateButtons();
    }
});

function applyState(items, popupHtml = null, meta = null, metaByProduct = null) {
    state.items = normalizeKeys(items);
    const previousMeta = state.meta || {};
    const previousMetaByProduct = state.metaByProduct || {};
    if (meta && typeof meta === "object") {
        state.meta = meta;
    } else {
        const filtered = {};
        state.items.forEach((key) => {
            if (previousMeta[key]) {
                filtered[key] = previousMeta[key];
            }
        });
        state.meta = filtered;
    }

    if (metaByProduct && typeof metaByProduct === "object") {
        state.metaByProduct = metaByProduct;
    } else {
        state.metaByProduct = previousMetaByProduct;
    }

    if (typeof window !== "undefined") {
        window.__FAVORITES__ = {
            items: [...state.items],
            count: state.items.length,
            isAuthorized: state.isAuthorized,
            meta: state.meta,
            metaByProduct: state.metaByProduct,
        };
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
            metaByProduct: state.metaByProduct,
        },
    }));
}

function handleButtonClick(event) {
    const target = event.target.closest(FAVORITE_BUTTON_SELECTOR);
    if (!target) {
        return;
    }

    const productId = getProductId(target);
    if (!productId) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    const priceSnapshot = captureCurrentPrice(target.closest(FAVORITE_PRODUCT_SELECTOR));
    const optionsData = extractOptionsData(target, productId);
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

function notifyFavoriteError() {
    const content = BX?.message?.ERROR_FAVORITES_TOGGLE || "Error updating favorites.";

    const show = () => {
        if (!BX?.UI?.Notification?.Center) {
            return;
        }

        BX.UI.Notification.Center.notify({
            content,
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

function toggleFavorite(productId, button, priceSnapshot, optionsOverride = null) {
    if (typeof BX === "undefined" || !BX.ajax || typeof BX.ajax.runComponentAction !== "function") {
        return;
    }

    button?.classList.add("is-processing");

    const options = normalizeOptionsPayload(optionsOverride ?? extractOptionsData(button, productId));

    BX.ajax.runComponentAction("brakes:favorites.sync", "toggle", {
        mode: "class",
        data: { productId, options },
    }).then((response) => {
        const data = response?.data;
        if (!data || data.status !== "success" || !Array.isArray(data.items)) {
            throw new Error("Unexpected response format");
        }

        applyState(data.items, data.popupHtml, data.meta, data.metaByProduct);

        button?.classList.remove("is-processing");
    }).catch((error) => {
        notifyFavoriteError();
        button?.classList.remove("is-processing");
    });
}


function refreshFromServer() {
    if (typeof BX === "undefined" || !BX.ajax || typeof BX.ajax.runComponentAction !== "function") {
        return Promise.resolve(state.items);
    }

    return BX.ajax.runComponentAction("brakes:favorites.sync", "list", {
        mode: "class",
    }).then((response) => {
        const data = response?.data;
        if (!data || data.status !== "success" || !Array.isArray(data.items)) {
            throw new Error("Unexpected response format");
        }

        applyState(data.items, data.popupHtml, data.meta, data.metaByProduct);

        return state.items;
    }).catch((error) => {
        return state.items;
    });
}

function initFavorites() {
    applyState(state.items);
    initPriceObservers();

    if (productMutationObserver) {
        productMutationObserver.observe(document.body, { childList: true, subtree: true });
    }

    document.addEventListener("click", handleButtonClick, true);
    document.addEventListener("productOptions:changed", handleProductOptionsChanged);
}

window.addEventListener("load", initFavorites);

window.brakesFavorites = {
    getIds: () => [...state.items],
    has: (id, context = null) => {
        if (typeof id === "string" && id.includes(":")) {
            return state.items.includes(id);
        }
        const productId = parseInt(id, 10);
        if (!productId) {
            return false;
        }
        const key = buildFavoriteKey(productId, context);
        return key ? state.items.includes(key) : false;
    },
    toggle: (id, options) => {
        if (typeof id === "string" && id.includes(":")) {
            const parsed = parseFavoriteKey(id);
            if (!parsed || !parsed.productId) {
                return;
            }
            const payload = typeof options === "object" && options ? { ...options } : {};
            if (parsed.context && !payload.context) {
                payload.context = parsed.context;
            }
            return toggleFavorite(parsed.productId, null, null, payload);
        }
        return toggleFavorite(parseInt(id, 10), null, null, options || null);
    },
    refresh: refreshFromServer,
};

function parseOptions(value) {
    if (!value) {
        return null;
    }

    if (typeof value === "object") {
        return value;
    }

    if (typeof value === "string") {
        try {
            const parsed = JSON.parse(value);
            return parsed;
        } catch (error) {
            console.warn("Favorites: failed to parse options JSON", error);
        }
    }

    return null;
}

function normalizeOptionsPayload(options, context = null) {
    const parsed = parseOptions(options);
    let normalizedContext = null;

    if (context && typeof context === "object") {
        const sectionId = parseInt(context.section_id || context.sectionId || context.section, 10);
        const sectionPath = typeof context.section_path === "string" ? context.section_path : context.sectionPath;
        const ctx = {};
        if (sectionId > 0) {
            ctx.section_id = sectionId;
        }
        if (sectionPath && typeof sectionPath === "string" && sectionPath.trim() !== "") {
            ctx.section_path = sectionPath.trim();
        }
        if (Object.keys(ctx).length > 0) {
            normalizedContext = ctx;
        }
    }

    if (!normalizedContext && parsed && typeof parsed === "object" && parsed.context && typeof parsed.context === "object") {
        const sectionId = parseInt(parsed.context.section_id || parsed.context.sectionId || parsed.context.section, 10);
        const sectionPath = typeof parsed.context.section_path === "string" ? parsed.context.section_path : parsed.context.sectionPath;
        const ctx = {};
        if (sectionId > 0) {
            ctx.section_id = sectionId;
        }
        if (sectionPath && typeof sectionPath === "string" && sectionPath.trim() !== "") {
            ctx.section_path = sectionPath.trim();
        }
        if (Object.keys(ctx).length > 0) {
            normalizedContext = ctx;
        }
    }

    if (!parsed || typeof parsed !== "object") {
        return normalizedContext ? { options: {}, context: normalizedContext } : { options: {} };
    }

    let source = parsed;
    if (source && typeof source === "object" && source.options && typeof source.options === "object") {
        source = source.options;
    }

    if (!source || typeof source !== "object") {
        return normalizedContext ? { options: {}, context: normalizedContext } : { options: {} };
    }

    const normalized = {};
    Object.keys(source).forEach((key) => {
        if (!key) {
            return;
        }
        let rawValue = source[key];
        if (rawValue && typeof rawValue === "object" && Object.prototype.hasOwnProperty.call(rawValue, "value")) {
            rawValue = rawValue.value;
        } else if (rawValue && typeof rawValue === "object" && Object.prototype.hasOwnProperty.call(rawValue, "VALUE")) {
            rawValue = rawValue.VALUE;
        }

        if (rawValue === undefined || rawValue === null) {
            return;
        }

        const normalizedKey = String(key).toLowerCase();
        normalized[normalizedKey] = String(rawValue).toLowerCase();
    });

    const payload = { options: normalized };
    if (normalizedContext) {
        payload.context = normalizedContext;
    }
    return payload;
}

function hasOptions(payload) {
    const hasContext = Boolean(payload && payload.context && Object.keys(payload.context).length > 0);
    const hasOpt = Boolean(payload && payload.options && Object.keys(payload.options).length > 0);
    return hasOpt || hasContext;
}

function extractContext(element) {
    if (!element) {
        return null;
    }
    const resolve = (node) => {
        const dataset = node?.dataset || {};
        const sectionId = parseInt(dataset.contextSectionId || 0, 10);
        const sectionPath = dataset.contextPath ? String(dataset.contextPath) : "";

        const context = {};
        if (sectionId > 0) {
            context.section_id = sectionId;
        }
        if (sectionPath && sectionPath.trim() !== "") {
            context.section_path = sectionPath.trim();
        }

        return Object.keys(context).length ? context : null;
    };

    const direct = resolve(element);
    if (direct) {
        return direct;
    }

    const container = element.closest(FAVORITE_PRODUCT_SELECTOR);
    if (container && container !== element) {
        return resolve(container);
    }

    return null;
}

function extractOptionsData(element, productId) {
    const context = extractContext(element);
    const favoriteKey = getFavoriteKeyFromElement(element);

    if (element) {
        const direct = normalizeOptionsPayload(element.dataset?.options, context);
        if (hasOptions(direct)) {
            return direct;
        }

        const container = element.closest(FAVORITE_PRODUCT_SELECTOR)
            || element.closest(".main-cataloge__item")
            || element.closest(".main-details")
            || element.closest(".main__details");

        if (container) {
            const button = container.querySelector("[data-fls-like-button]");
            if (button && button !== element) {
                const parsed = normalizeOptionsPayload(button.dataset?.options, extractContext(button) || context);
                if (hasOptions(parsed)) {
                    return parsed;
                }
            }
        }
    }

    if (favoriteKey && state.meta && state.meta[favoriteKey] && state.meta[favoriteKey].options) {
        const metaPayload = normalizeOptionsPayload(state.meta[favoriteKey].options, state.meta[favoriteKey].context || null);
        if (hasOptions(metaPayload)) {
            return metaPayload;
        }
    }

    if (productId && state.metaByProduct && state.metaByProduct[productId] && state.metaByProduct[productId].options) {
        const metaPayload = normalizeOptionsPayload(state.metaByProduct[productId].options, state.metaByProduct[productId].context || null);
        if (hasOptions(metaPayload)) {
            return metaPayload;
        }
    }

    return context ? { options: {}, context } : { options: {} };
}

function handleProductOptionsChanged(event) {
    const detail = event?.detail || {};
    const productId = parseInt(detail.productId, 10);
    if (!productId) {
        return;
    }

    const context = detail.context || null;
    const favoriteKey = detail.favoriteKey || buildFavoriteKey(productId, context);
    if (!favoriteKey || !state.items.includes(favoriteKey)) {
        return;
    }

    const options = normalizeOptionsPayload(detail.options, context);
    scheduleFavoriteUpdate(productId, favoriteKey, options);
}

function scheduleFavoriteUpdate(productId, favoriteKey, options) {
    const key = favoriteKey || String(productId);
    if (pendingUpdateTimers.has(key)) {
        clearTimeout(pendingUpdateTimers.get(key));
    }

    const payload = {
        options: { ...(options?.options || {}) },
        context: options?.context || null,
    };

    const timer = setTimeout(() => {
        pendingUpdateTimers.delete(key);
        sendUpdateRequest(productId, payload);
    }, 200);

    pendingUpdateTimers.set(key, timer);
}

function sendUpdateRequest(productId, options) {
    if (typeof BX === "undefined" || !BX.ajax || typeof BX.ajax.runComponentAction !== "function") {
        return;
    }

    BX.ajax.runComponentAction("brakes:favorites.sync", "update", {
        mode: "class",
        data: { productId, options },
    }).then((response) => {
        const data = response?.data;
        if (!data || data.status !== "success" || !Array.isArray(data.items)) {
            throw new Error("Unexpected response format");
        }

        applyState(data.items, data.popupHtml, data.meta, data.metaByProduct);
    }).catch((error) => {
    });
}
