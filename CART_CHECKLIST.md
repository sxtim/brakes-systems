# Cart and Checkout Checklist (Bitrix Canonical Path)

Sources:
- sale.basket.basket: https://dev.1c-bitrix.ru/user_help/components/magazin/basket/sale_basket_basket.php
- sale.order.ajax: https://dev.1c-bitrix.ru/user_help/components/magazin/zakaz/sale_order_ajax.php
- D7 basket: https://mrcappuccino.ru/blog/post/work-with-basket-bitrix-d7
- D7 order: https://mrcappuccino.ru/blog/post/work-with-order-bitrix-d7
- Order course: https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=42&CHAPTER_ID=04836

Scope:
- Use standard Bitrix components for basket and checkout.
- Use D7 API to add items to basket with context properties (mark/model/body).
- Keep UI layout from current templates.

Phase 0. Preconditions
- [ ] Modules: sale and catalog enabled.
- [ ] Currency and base price type configured.
- [ ] Order properties defined (Name, Phone, Address, Email).
- [ ] Delivery services configured (at least 1).
- [ ] Payment systems configured (at least 1).
- [ ] Decide URL paths:
  - Basket page: /personal/cart/ (current BASKET_URL in catalog/index.php)
  - Order page: /personal/order/

Phase 1. Basket page (sale.basket.basket)
- [ ] Create /personal/cart/index.php and include sale.basket.basket.
- [ ] Configure key params (recommended):
  - PATH_TO_ORDER = /personal/order/
  - ACTION_VARIABLE = action
  - PRICE_DISPLAY_MODE = Y
  - QUANTITY_FLOAT = N (Y only if fractional qty in catalog)
  - CORRECT_RATIO = Y (if ratio used)
  - AUTO_CALCULATION = Y
  - SHOW_FILTER = N (enable later if needed)
  - SHOW_RESTORE = Y
  - DEFERRED_REFRESH = Y (optional for perf)
  - COMPATIBLE_MODE = N (new template)
- [ ] Create template:
  - local/templates/main/components/bitrix/sale.basket.basket/.default/
  - Map HTML to basket__* classes.
  - Add empty.php if custom empty basket view is needed.
- [ ] Verify AJAX update works (qty change, delete, coupon if used).

Phase 2. Add-to-basket pipeline (D7)
- [ ] Create endpoint (local/ajax/add_to_basket.php or component action).
- [ ] Input: product_id, quantity, context_section_id, context_path, context_label, options.
- [ ] D7 flow:
  - Basket::loadItemsForFUser(Fuser::getId(), SITE_ID)
  - $item = $basket->getExistsItem("catalog", $productId) or createItem
  - $item->setField("QUANTITY", $qty)
  - Set basket properties (context + options):
    - CONTEXT_SECTION_ID, CONTEXT_PATH, CONTEXT_LABEL, OPTIONS_JSON
  - $basket->save()
- [ ] UI hooks:
  - Buttons in product cards already have data-context-* and data-options.
  - Add JS handler to call endpoint and refresh basket counters.

Phase 3. Checkout page (sale.order.ajax)
- [ ] Create /personal/order/index.php and include sale.order.ajax.
- [ ] Key params (recommended):
  - ALLOW_AUTO_REGISTER = Y (guest checkout)
  - SEND_NEW_USER_NOTIFY = N (keep silent auto-register)
  - ALLOW_APPEND_ORDER = Y (reuse existing user by email)
  - DELIVERY_NO_AJAX = A (default flow)
  - DELIVERY_TO_PAYSYSTEM = D2P or P2D (choose)
  - USE_PRELOAD = Y (prefill from last order)
  - ALLOW_USER_PROFILES = Y (optional)
  - TEMPLATE_LOCATION = "search" (if locations used)
- [ ] Ensure order properties map to correct Person Type.
- [ ] Verify order creation from basket with context properties preserved.

Phase 4. Context-aware basket items (critical for clones)
- [ ] Add basket item properties for context to avoid merging:
  - CONTEXT_SECTION_ID (int)
  - CONTEXT_PATH (string)
  - CONTEXT_LABEL (string)
  - OPTIONS_JSON (string)
- [ ] In basket template, show context label under product title.
- [ ] In order details/admin, make properties visible if needed.

Phase 5. Testing checklist
- [ ] Add same product with different contexts -> separate basket lines.
- [ ] Update qty, delete, restore -> OK.
- [ ] Place order as guest -> order created, user registered silently.
- [ ] Context properties visible in order admin view.
- [ ] Stock and price sync unaffected (single SKU, shared quantity).

Optional (later)
- [ ] Mini-basket (sale.basket.line) in header.
- [ ] Coupons and discount rules.
- [ ] Add-to-basket analytics events.

---

Русский перевод

Источники:
- sale.basket.basket: https://dev.1c-bitrix.ru/user_help/components/magazin/basket/sale_basket_basket.php
- sale.order.ajax: https://dev.1c-bitrix.ru/user_help/components/magazin/zakaz/sale_order_ajax.php
- D7 корзина: https://mrcappuccino.ru/blog/post/work-with-basket-bitrix-d7
- D7 заказ: https://mrcappuccino.ru/blog/post/work-with-order-bitrix-d7
- Курс по заказам: https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=42&CHAPTER_ID=04836

Область:
- Используем штатные компоненты корзины и оформления.
- Добавление в корзину через D7 API с контекстными свойствами (марка/модель/кузов).
- Верстку оставляем текущую.

Фаза 0. Предусловия
- [ ] Модули sale и catalog включены.
- [ ] Валюта и базовый тип цены настроены.
- [ ] Свойства заказа настроены (Имя, Телефон, Адрес, Email).
- [ ] Службы доставки настроены (минимум одна).
- [ ] Платежные системы настроены (минимум одна).
- [ ] Решены URL:
  - Корзина: /personal/cart/ (текущее BASKET_URL в catalog/index.php)
  - Оформление: /personal/order/

Фаза 1. Корзина (sale.basket.basket)
- [ ] Создать /personal/cart/index.php и подключить sale.basket.basket.
- [ ] Базовые параметры (рекомендовано):
  - PATH_TO_ORDER = /personal/order/
  - ACTION_VARIABLE = action
  - PRICE_DISPLAY_MODE = Y
  - QUANTITY_FLOAT = N (Y только если в каталоге дробное количество)
  - CORRECT_RATIO = Y (если используются коэффициенты)
  - AUTO_CALCULATION = Y
  - SHOW_FILTER = N (включить позже при необходимости)
  - SHOW_RESTORE = Y
  - DEFERRED_REFRESH = Y (по желанию, для ускорения)
  - COMPATIBLE_MODE = N (новый шаблон)
- [ ] Шаблон компонента:
  - local/templates/main/components/bitrix/sale.basket.basket/.default/
  - Сопоставить HTML с классами basket__*
  - При необходимости добавить empty.php для пустой корзины.
- [ ] Проверить AJAX‑пересчет (кол-во, удаление, купоны).

Фаза 2. Добавление в корзину (D7)
- [ ] Создать endpoint (local/ajax/add_to_basket.php или action компонента).
- [ ] Вход: product_id, quantity, context_section_id, context_path, context_label, options.
- [ ] Поток D7:
  - Basket::loadItemsForFUser(Fuser::getId(), SITE_ID)
  - $item = $basket->getExistsItem("catalog", $productId) или createItem
  - $item->setField("QUANTITY", $qty)
  - Свойства корзины (контекст + опции):
    - CONTEXT_SECTION_ID, CONTEXT_PATH, CONTEXT_LABEL, OPTIONS_JSON
  - $basket->save()
- [ ] UI:
  - В карточках уже есть data-context-* и data-options.
  - Добавить JS‑обработчик, который дергает endpoint и обновляет счетчик корзины.

Фаза 3. Оформление (sale.order.ajax)
- [ ] Создать /personal/order/index.php и подключить sale.order.ajax.
- [ ] Параметры (рекомендовано):
  - ALLOW_AUTO_REGISTER = Y (гостевой заказ)
  - SEND_NEW_USER_NOTIFY = N (без писем)
  - ALLOW_APPEND_ORDER = Y (привязка к пользователю по email)
  - DELIVERY_NO_AJAX = A (стандартный режим)
  - DELIVERY_TO_PAYSYSTEM = D2P или P2D (выбрать)
  - USE_PRELOAD = Y (подстановка из предыдущих заказов)
  - ALLOW_USER_PROFILES = Y (опционально)
  - TEMPLATE_LOCATION = "search" (если используем местоположения)
- [ ] Убедиться, что свойства заказа привязаны к типу плательщика.
- [ ] Проверить, что заказ создается и контекстные свойства в корзине сохраняются.

Фаза 4. Контекстные свойства корзины (важно для клонов)
- [ ] Обязательные свойства, чтобы позиции не склеивались:
  - CONTEXT_SECTION_ID (int)
  - CONTEXT_PATH (string)
  - CONTEXT_LABEL (string)
  - OPTIONS_JSON (string)
- [ ] В шаблоне корзины показывать CONTEXT_LABEL под названием товара.
- [ ] При необходимости выводить эти свойства в админке заказа.

Фаза 5. Тесты
- [ ] Один товар с разными контекстами -> разные позиции.
- [ ] Изменение количества, удаление, восстановление -> работает.
- [ ] Гостевой заказ -> создается, пользователь регистрируется тихо.
- [ ] Контекстные свойства видны в заказе.
- [ ] Цены/остатки корректны (один SKU, общий остаток).

Опционально (позже)
- [ ] Мини‑корзина в шапке (sale.basket.line).
- [ ] Купоны и правила скидок.
- [ ] Аналитика add‑to‑basket.
