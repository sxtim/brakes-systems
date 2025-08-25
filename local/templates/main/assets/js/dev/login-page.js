import "./app.min.js";
import "./popup.min.js";
import "./cataloge.min.js";
/* empty css           */
document.addEventListener("DOMContentLoaded", function() {
  const buttons = document.querySelectorAll(".tab-btn");
  const contents = document.querySelectorAll(".content");
  function activateTab(index) {
    buttons.forEach((btn) => {
      btn.classList.toggle("active", btn.dataset.tab === index);
    });
    if (window.innerWidth <= 992) {
      contents.forEach((cont) => {
        cont.classList.toggle("active", cont.dataset.content === index);
      });
    } else {
      contents.forEach((cont) => {
        cont.classList.add("active");
      });
    }
  }
  buttons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const index = btn.dataset.tab;
      activateTab(index);
    });
  });
  function handleResize() {
    if (window.innerWidth > 992) {
      contents.forEach((cont) => cont.classList.add("active"));
    } else {
      const activeBtn = document.querySelector(".tab-btn.active");
      activateTab(activeBtn ? activeBtn.dataset.tab : "1");
    }
  }
  window.addEventListener("resize", handleResize);
  handleResize();
});
