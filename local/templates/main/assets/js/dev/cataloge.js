document.querySelectorAll(".selector-row").forEach((row) => {
  row.addEventListener("click", function(e) {
    const clicked = e.target.closest(".selector-item");
    if (!clicked) return;
    row.querySelectorAll(".selector-item").forEach((item) => {
      item.classList.remove("active");
    });
    clicked.classList.add("active");
  });
});
const slides = document.querySelectorAll(".selector-row__slide");
slides.forEach((slide) => {
  slide.addEventListener("click", function() {
    slides.forEach((s) => s.classList.remove("active"));
    slide.classList.add("active");
  });
});
const slides2 = document.querySelectorAll(".selector-row__slide-2");
slides2.forEach((slide) => {
  slide.addEventListener("click", function() {
    slides2.forEach((s) => s.classList.remove("active"));
    slide.classList.add("active");
  });
});
document.addEventListener("DOMContentLoaded", () => {
  const selectContainer = document.getElementById("sort-select");
  const toggleButton = selectContainer.querySelector(".select-toggle");
  const optionsList = selectContainer.querySelector(".select-options");
  const options = optionsList.querySelectorAll(".option");
  const closeSelect = () => {
    optionsList.classList.remove("show");
    toggleButton.setAttribute("aria-expanded", "false");
  };
  const openSelect = () => {
    optionsList.classList.add("show");
    toggleButton.setAttribute("aria-expanded", "true");
  };
  toggleButton.addEventListener("click", (e) => {
    e.stopPropagation();
    if (optionsList.classList.contains("show")) {
      closeSelect();
      toggleButton.classList.remove("active");
    } else {
      openSelect();
      toggleButton.classList.add("active");
    }
  });
  options.forEach((option) => {
    option.addEventListener("click", (e) => {
      e.stopPropagation();
      option.dataset.value;
      toggleButton.textContent = option.textContent;
      options.forEach((opt) => opt.classList.remove("active"));
      option.classList.add("active");
      closeSelect();
    });
  });
  document.addEventListener("click", () => {
    closeSelect();
  });
});
document.addEventListener("DOMContentLoaded", function() {
  const gridBtn = document.querySelector(".main-cataloge__btn-grid");
  const listBtn = document.querySelector(".main-cataloge__btn-list");
  const catalogBody = document.querySelector(".main-cataloge__body");
  const buttons = [gridBtn, listBtn];
  buttons.forEach((button) => {
    button.addEventListener("click", function() {
      buttons.forEach((btn) => btn.classList.remove("active"));
      this.classList.add("active");
      catalogBody.classList.remove("view-grid", "view-list");
      const view = this.getAttribute("data-view");
      catalogBody.classList.add("view-" + view);
    });
  });
});
document.querySelectorAll(".main-cataloge__colors-box").forEach((colorsBox) => {
  colorsBox.addEventListener("click", (e) => {
    if (e.target.classList.contains("main-cataloge__colors-item")) {
      colorsBox.querySelectorAll(".main-cataloge__colors-item").forEach((btn) => {
        btn.classList.remove("active");
      });
      e.target.classList.add("active");
    }
  });
});
