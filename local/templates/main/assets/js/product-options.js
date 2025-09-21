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
  handleOneClickBuyButtons();
});

// Функция для обработки кнопок "Купить в один клик"
function handleOneClickBuyButtons() {
  document.querySelectorAll('[data-fls-popup-link="speedBuy"]').forEach(button => {
    button.addEventListener('click', function(e) {
      const buyButton = e.currentTarget; // Используем currentTarget, так как слушатель на самой кнопке

      const popup = document.querySelector('.popup[data-fls-popup="speedBuy"]');
      if (!popup) {
          return;
      }

      // Получаем все данные из data-атрибутов кнопки
      const productName = buyButton.dataset.productName || '';
      const productPrice = buyButton.dataset.productPrice || ''; // Цена теперь берется из DOM, но на всякий случай
      const productUrl = buyButton.dataset.productUrl || '';
      const options = buyButton.dataset.options || '';

      // Находим элементы цены на странице (для детальной и каталога)
      const itemContainer = buyButton.closest('.main-cataloge__item') || buyButton.closest('.main__details');
      const priceElement = itemContainer ? itemContainer.querySelector('.main-details__price-new, .main-cataloge__price') : null;
      const actualPrice = priceElement ? priceElement.textContent.trim() : productPrice; // Берем актуальную цену

      // Формируем читаемую строку опций
      let optionsString = '';
      const keyMap = {
          'two_piece_disc_construction': 'Двусоставная конструкция диска',
          'rotor_pattern': 'Рисунок ротора',
          'caliper_logo': 'Лого на суппорт',
          'electric_handbrake': 'Электроручник'
      };
      const valueMap = {
          'no': 'Нет',
          'yes': 'Да',
          'standard': 'Стандартный',
          'special': 'Особый логотип',
          'perforation': 'Перфорация',
          'slots': 'Насечки',
          'perforation_slots': 'Перфорация + насечки'
      };

      try {
          if (options && options !== '{}') {
              const optionsData = JSON.parse(options);
              const optionsArray = [];
              if (optionsData && typeof optionsData.options === 'object') {
                   for (const key in optionsData.options) {
                      const rawValue = optionsData.options[key].value;
                      if (keyMap[key] && rawValue) {
                          const translatedValue = valueMap[rawValue.toLowerCase()] || rawValue;
                          optionsArray.push(`${keyMap[key]}: ${translatedValue}`);
                      }
                  }
              }
              optionsString = optionsArray.join(', ');
          }
      } catch (error) {
          console.error('Error parsing options data:', error);
          optionsString = options || 'Ошибка чтения опций';
      }

      // Находим все инпуты в попапе
      const productNameInput = popup.querySelector('input[data-product-input="name"]');
      const productPriceInput = popup.querySelector('input[data-product-input="price"]');
      const productUrlInput = popup.querySelector('input[data-product-input="url"]');
      const productOptionsInput = popup.querySelector('input[data-product-input="options"]');

      // Заполняем инпуты
      if (productNameInput) productNameInput.value = productName;
      if (productPriceInput) productPriceInput.value = actualPrice;
      if (productUrlInput) productUrlInput.value = productUrl;
      if (productOptionsInput) productOptionsInput.value = optionsString;
    });
  });
}

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
    : productContainer.querySelectorAll('[data-fls-addtocart-button], .main-cataloge__shoping-btn, .main-cataloge__buy');
    
  buyButtons.forEach(button => {
    button.setAttribute('data-options', JSON.stringify(optionsData));
  });
}