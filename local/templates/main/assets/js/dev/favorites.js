const FAVORITE_BUTTON_SELECTOR = "[data-fls-like-button]";
const FAVORITE_PRODUCT_SELECTOR = "[data-fls-like-product]";
const FAVORITE_COUNTER_SELECTOR = "[data-fls-like]";
const ACTIVE_CLASS = "liked";
const FAVORITE_POPUP_BODY_SELECTOR = ".favorit-box__body";
const PRICE_SELECTORS = [".main-details__price-new", ".main-cataloge__price"];

const initialState = window.__FAVORITES__ || { items: [], count: 0, isAuthorized: false };
const state = {
    items: normalizeIds(initialState.items || []),
    isAuthorized: Boolean(initialState.isAuthorized),
};

const priceObservers = new WeakMap();
const lastPriceHtml = new WeakMap();

function applyPriceToPopup(id, priceHtml, priceText) {
    const itemNode = document.querySelector(`[data-fls-like-product="${id}"]`);
    if (!itemNode) {
        return;
    }

    const priceNode = itemNode.querySelector('.favorit-box__item-price');
    if (!priceNode) {
        return;
    }

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
            return;
        }

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

function applyState(items, popupHtml = null) {
    state.items = normalizeIds(items);
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

    toggleFavorite(productId, target, priceSnapshot);
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

function toggleFavorite(productId, button, priceSnapshot) {
    if (typeof BX === "undefined" || !BX.ajax || typeof BX.ajax.runComponentAction !== "function") {
        console.error("Favorites: Bitrix ajax is not available.");
        return;
    }

    button?.classList.add("is-processing");

    BX.ajax.runComponentAction("brakes:favorites.sync", "toggle", {
        mode: "class",
        data: { productId },
    }).then((response) => {
        const data = response?.data;
        if (!data || data.status !== "success" || !Array.isArray(data.items)) {
            throw new Error("Unexpected response format");
        }

        applyState(data.items, data.popupHtml);

        button?.classList.remove("is-processing");
    }).catch((error) => {
        console.error("Favorites: toggle failed", error);
        if (BX?.UI?.Notification?.Center) {
            BX.UI.Notification.Center.notify({
                content: BX.message?.ERROR_FAVORITES_TOGGLE || "Не удалось обновить избранное.",
                autoHideDelay: 5000,
                position: "top-right",
            });
        }
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

        applyState(data.items, data.popupHtml);

        return state.items;
    }).catch((error) => {
        console.error("Favorites: refresh failed", error);
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
}

window.addEventListener("load", initFavorites);

window.brakesFavorites = {
    getIds: () => [...state.items],
    has: (id) => state.items.includes(parseInt(id, 10)),
    toggle: (id) => toggleFavorite(parseInt(id, 10)),
    refresh: refreshFromServer,
};
