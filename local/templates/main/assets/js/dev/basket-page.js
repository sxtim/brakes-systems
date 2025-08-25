import "./app.min.js";
import "./popup.min.js";
import "./cataloge.min.js";
document.addEventListener("DOMContentLoaded", function() {
  const cartItems = document.querySelectorAll(".basket__item");
  cartItems.forEach((item) => {
    const minusBtn = item.querySelector(".quantity-btn.minus");
    const plusBtn = item.querySelector(".quantity-btn.plus");
    const input = item.querySelector(".quantity-input");
    minusBtn.addEventListener("click", () => {
      let value = parseInt(input.value);
      if (value > 1) {
        input.value = value - 1;
      }
    });
    plusBtn.addEventListener("click", () => {
      let value = parseInt(input.value);
      input.value = value + 1;
    });
    input.addEventListener("input", () => {
      let value = parseInt(input.value);
      if (isNaN(value) || value < 1) {
        input.value = 1;
      }
    });
  });
});
document.querySelector(".basket__products").addEventListener("click", function(e) {
  const deleteBasketBtn = e.target.closest(".basket__delete");
  if (deleteBasketBtn) {
    const item = deleteBasketBtn.closest(".basket__item");
    if (item) {
      item.remove();
    }
  }
});
