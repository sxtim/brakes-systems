import "./app.min.js";
import "./popup.min.js";
import "./cataloge.min.js";

function clampQuantity(value) {
  const parsed = parseFloat(value);
  if (!Number.isFinite(parsed) || parsed <= 0) {
    return 1;
  }
  return Math.max(1, parsed);
}

function requestBasketAction(action, data) {
  if (!BX?.ajax?.runComponentAction) {
    return Promise.reject(new Error("BX ajax unavailable"));
  }

  return BX.ajax.runComponentAction("brakes:basket.actions", action, {
    mode: "class",
    data,
  });
}

function updateItemQuantity(itemId, quantity) {
  return requestBasketAction("update", {
    basketItemId: itemId,
    quantity,
  });
}

function removeItem(itemId) {
  return requestBasketAction("remove", {
    basketItemId: itemId,
  });
}

document.addEventListener("DOMContentLoaded", function() {
  const products = document.querySelector(".basket__products");
  if (!products) {
    return;
  }

  products.addEventListener("click", function(event) {
    const deleteBtn = event.target.closest(".basket__delete");
    if (deleteBtn) {
      const itemId = parseInt(deleteBtn.dataset.basketItemId || "0", 10) || 0;
      if (itemId > 0) {
        removeItem(itemId).then(() => {
          window.location.reload();
        }).catch(() => {});
      }
      return;
    }

    const stepBtn = event.target.closest("[data-basket-qty-step]");
    if (!stepBtn) {
      return;
    }

    const item = stepBtn.closest("[data-basket-item-id]");
    if (!item) {
      return;
    }

    const input = item.querySelector("[data-basket-qty]");
    if (!input) {
      return;
    }

    const step = parseFloat(stepBtn.dataset.basketQtyStep || "0") || 0;
    const nextValue = clampQuantity(parseFloat(input.value || "1") + step);
    input.value = nextValue;

    const itemId = parseInt(item.dataset.basketItemId || "0", 10) || 0;
    if (itemId > 0) {
      updateItemQuantity(itemId, nextValue).then(() => {
        window.location.reload();
      }).catch(() => {});
    }
  });

  products.addEventListener("change", function(event) {
    const input = event.target.closest("[data-basket-qty]");
    if (!input) {
      return;
    }

    const item = input.closest("[data-basket-item-id]");
    if (!item) {
      return;
    }

    const normalized = clampQuantity(input.value);
    input.value = normalized;

    const itemId = parseInt(item.dataset.basketItemId || "0", 10) || 0;
    if (itemId > 0) {
      updateItemQuantity(itemId, normalized).then(() => {
        window.location.reload();
      }).catch(() => {});
    }
  });
});
