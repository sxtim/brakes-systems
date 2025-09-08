document.querySelectorAll(".spollers__item").forEach((spoller) => {
  spoller.addEventListener("click", (e) => {
    const clickedItem = e.target.closest(".main-cataloge__sublist-item");
    if (!clickedItem) return;

    e.preventDefault();
    e.stopPropagation();

    const currentSpoller = clickedItem.closest(".spollers__item");
    currentSpoller.querySelectorAll(".main-cataloge__sublist-item").forEach((item) => {
      item.classList.remove("selected");
    });

    clickedItem.classList.add("selected");

    const productCard = currentSpoller.closest(".main-cataloge__item");
    if (productCard) {
      updateProductPrice(productCard);
      updateProductOptions(productCard);
    } else {
      updateProductPrice(document.body);
      updateProductOptions(document.body);
    }
  });
});

document.addEventListener("DOMContentLoaded", () => {
  updateProductOptions(document.body);
});

function updateProductPrice(productCard) {
  let priceElement = productCard.querySelector(".main-cataloge__price");
  if (!priceElement) {
    priceElement = productCard.querySelector(".main-details__price-new");
  }
  if (!priceElement) {
    const parentContainer = document.querySelector(".main-details__price");
    if (parentContainer) {
      priceElement = parentContainer.querySelector(".main-details__price-new");
    }
  }
  if (!priceElement) return;

  const container = priceElement.closest(".main-cataloge__item") || document.body;
  if (!container.dataset.basePrice) {
    const basePriceText = priceElement.textContent.replace(/[^0-9]/g, "");
    container.dataset.basePrice = parseInt(basePriceText) || 0;
  }
  
  const basePrice = parseInt(container.dataset.basePrice);
  let totalMarkup = 0;
  
  const allSpollersInProduct = document.querySelectorAll(".spollers__item");
  allSpollersInProduct.forEach((spoller) => {
    const selectedOption = spoller.querySelector(".main-cataloge__sublist-item.selected");
    if (!selectedOption) return;
    
    const optionText = selectedOption.textContent.trim().toLowerCase();
    const featureTitleElement = spoller.querySelector(".main-cataloge__feature-item, .main-details__feature-item");
    if (!featureTitleElement) return;
    
    const featureTitle = featureTitleElement.textContent;
    
    if (optionText === "да" && featureTitle.includes("Двусоставная")) {
      totalMarkup += 10000;
    } else if (optionText === "особый логотип") {
      totalMarkup += 5000;
    } else if (optionText === "да" && featureTitle.includes("Электроручник")) {
      totalMarkup += 50000;
    } else if (featureTitle.includes("Рисунок ротора")) {
      if (optionText === "НЕТ") {
        // No markup for "НЕТ" option
      } else if (optionText.includes("перфорация") && optionText.includes("насечки")) {
        totalMarkup += 30000;
      } else if (optionText.includes("перфорация")) {
        totalMarkup += 18000;
      } else if (optionText.includes("насечки")) {
        totalMarkup += 20000;
      }
    }
  });

  const finalPrice = basePrice + totalMarkup;
  priceElement.textContent = finalPrice.toLocaleString('ru-RU') + " ₽";
}

function updateProductOptions(productContainer) {
  const selectedOptions = {
    "two_piece_disc_construction": { value: "no" },
    "rotor_pattern": { value: "standard" },
    "caliper_logo": { value: "standard" },
    "electric_handbrake": { value: "no" }
  };
  
  const allSpollersInProduct = productContainer === document.body 
    ? document.querySelectorAll(".spollers__item")
    : productContainer.querySelectorAll(".spollers__item");
    
  allSpollersInProduct.forEach((spoller) => {
    const selectedOption = spoller.querySelector(".main-cataloge__sublist-item.selected");
    if (!selectedOption) return;
    
    const optionText = selectedOption.textContent.trim();
    const featureTitleElement = spoller.querySelector(".main-cataloge__feature-item, .main-details__feature-item");
    if (!featureTitleElement) return;
    
    const featureTitle = featureTitleElement.textContent.trim();
    
    let englishKey = featureTitle;
    let englishValue = optionText;
    
    if (featureTitle.includes("Двусоставная конструкция диска")) {
      englishKey = "two_piece_disc_construction";
      englishValue = optionText.toLowerCase() === "да" ? "yes" : "no";
    } else if (featureTitle.includes("Рисунок ротора")) {
      englishKey = "rotor_pattern";
      if (optionText === "НЕТ") {
        englishValue = "none";
      } else if (optionText.includes("ПЕРФОРАЦИЯ") && optionText.includes("НАСЕЧКИ")) {
        englishValue = "perforation_and_notches";
      } else if (optionText.includes("ПЕРФОРАЦИЯ")) {
        englishValue = "perforation";
      } else if (optionText.includes("НАСЕЧКИ")) {
        englishValue = "notches";
      }
    } else if (featureTitle.includes("Лого на суппорт")) {
      englishKey = "caliper_logo";
      if (optionText.toLowerCase().includes("особый")) {
        englishValue = "custom_logo";
      } else {
        englishValue = "standard";
      }
    } else if (featureTitle.includes("Электроручник")) {
      englishKey = "electric_handbrake";
      englishValue = optionText.toLowerCase() === "да" ? "yes" : "no";
    }
    
    selectedOptions[englishKey] = {
      value: englishValue
    };
  });
  
  const optionsData = {
    options: selectedOptions
  };
  
  const buyButtons = productContainer === document.body
    ? document.querySelectorAll('[data-fls-addtocart-button], [data-fls-popup-link="speedBuy"], .main-details__buy, .main-cataloge__shoping-btn')
    : productContainer.querySelectorAll('[data-fls-addtocart-button], .main-cataloge__shoping-btn');
    
  buyButtons.forEach(button => {
    button.setAttribute('data-options', JSON.stringify(optionsData));
  });
}