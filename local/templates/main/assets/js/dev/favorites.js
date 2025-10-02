const FAVORITE_BUTTON_SELECTOR = "[data-fls-like-button]";
const FAVORITE_PRODUCT_SELECTOR = "[data-fls-like-product]";
const FAVORITE_COUNTER_SELECTOR = "[data-fls-like]";
const ACTIVE_CLASS = "liked";

const initialState = window.__FAVORITES__ || { items: [], count: 0, isAuthorized: false };
const state = {
    items: normalizeIds(initialState.items || []),
    isAuthorized: Boolean(initialState.isAuthorized),
};

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

function applyState(items) {
    state.items = normalizeIds(items);
    updateCounter();
    updateButtons();

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

    toggleFavorite(productId, target);
}

function toggleFavorite(productId, button) {
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

        applyState(data.items);
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

        applyState(data.items);
        return state.items;
    }).catch((error) => {
        console.error("Favorites: refresh failed", error);
        return state.items;
    });
}

function initFavorites() {
    applyState(state.items);
    document.addEventListener("click", handleButtonClick, true);
}

window.addEventListener("load", initFavorites);

window.brakesFavorites = {
    getIds: () => [...state.items],
    has: (id) => state.items.includes(parseInt(id, 10)),
    toggle: (id) => toggleFavorite(parseInt(id, 10)),
    refresh: refreshFromServer,
};
