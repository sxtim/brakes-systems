window.addEventListener("load", function () {
    const RESEND_DELAY_MS = 60000;

    const resendIntervals = new Map();

    const popupComponentMap = new Map();



    function getResendButton(popupElement) {

        return popupElement ? popupElement.querySelector('.js-resend-code-btn') : null;

    }



    function getResendButtonTextElement(button) {

        if (!button) {

            return null;

        }

        return button.querySelector('.main-cataloge__shoping-text') || button;

    }



    function ensureResendDefaultText(button) {

        if (!button) {

            return null;

        }



        const textElement = getResendButtonTextElement(button);

        if (textElement && !button.dataset.defaultText) {

            button.dataset.defaultText = textElement.textContent.trim();

        }

        return textElement;

    }



    function updateResendButtonText(button, remainingMs) {

        const textElement = ensureResendDefaultText(button);

        if (!textElement) {

            return;

        }



        const baseText = button.dataset.defaultText || textElement.textContent.trim();

        const seconds = Math.max(0, Math.ceil(remainingMs / 1000));

        textElement.textContent = seconds > 0 ? `${baseText} (${seconds})` : baseText;

    }



    function stopResendCountdown(popupElement, restore = false) {

        if (!popupElement) {

            return;

        }



        const popupId = popupElement.id;

        const intervalId = resendIntervals.get(popupId);



        if (intervalId) {

            clearInterval(intervalId);

            resendIntervals.delete(popupId);

        }



        if (restore) {

            const button = getResendButton(popupElement);

            const textElement = ensureResendDefaultText(button);



            if (button) {

                button.disabled = false;

                button.classList.remove('is-loading');

            }



            if (button && textElement && button.dataset.defaultText) {

                textElement.textContent = button.dataset.defaultText;

            }



            delete popupElement.dataset.resendAvailableAt;

        } else if (popupElement.dataset.resendAvailableAt) {

            delete popupElement.dataset.resendAvailableAt;

        }

    }



    function setResendCooldown(popupElement, availableAt) {

        if (!popupElement) {

            return;

        }



        const button = getResendButton(popupElement);



        stopResendCountdown(popupElement);

        popupElement.dataset.resendAvailableAt = String(availableAt);



        if (!button) {

            return;

        }



        ensureResendDefaultText(button);

        button.disabled = true;

        button.classList.remove('is-loading');



        const update = () => {

            const remaining = availableAt - Date.now();

            if (remaining <= 0) {

                stopResendCountdown(popupElement, true);

                return;

            }



            updateResendButtonText(button, remaining);

        };



        update();

        const intervalId = window.setInterval(update, 1000);

        resendIntervals.set(popupElement.id, intervalId);

    }



    function isResendOnCooldown(popupElement) {

        if (!popupElement || !popupElement.dataset.resendAvailableAt) {

            return false;

        }



        const availableAt = Number(popupElement.dataset.resendAvailableAt);

        return Number.isFinite(availableAt) && availableAt > Date.now();

    }



    function restoreResendState(popupElement) {

        if (!popupElement) {

            return;

        }



        const availableAt = Number(popupElement.dataset.resendAvailableAt);

        if (Number.isFinite(availableAt) && availableAt > Date.now()) {

            setResendCooldown(popupElement, availableAt);

        } else {

            stopResendCountdown(popupElement, true);

        }

    }



    function toggleResendButtonLoading(button, isLoading, popupElement) {

        if (!button) {

            return;

        }



        const textElement = ensureResendDefaultText(button);



        if (isLoading) {

            button.disabled = true;

            button.classList.add('is-loading');

            if (textElement) {

                textElement.textContent = 'Отправка...';

            }

        } else {

            button.classList.remove('is-loading');

            if (!isResendOnCooldown(popupElement || button.closest('.popup'))) {

                button.disabled = false;

            }

            if (textElement && button.dataset.defaultText) {

                textElement.textContent = button.dataset.defaultText;

            }

        }

    }



    function extractPayloadFromForm(form) {

        if (!form) {

            return {};

        }



        const payload = {};

        const formData = new FormData(form);

        formData.forEach((value, key) => {

            payload[key] = value;

        });

        return payload;

    }



    function storeResendPayload(popupElement, payload) {

        if (!popupElement) {

            return;

        }



        if (payload && Object.keys(payload).length > 0) {

            popupElement.dataset.resendPayload = JSON.stringify(payload);

        } else if (popupElement.dataset.resendPayload) {

            delete popupElement.dataset.resendPayload;

        }

    }



    function getResendPayload(popupElement) {

        if (!popupElement) {

            return null;

        }



        const raw = popupElement.dataset.resendPayload;

        if (!raw) {

            return null;

        }



        try {

            const parsed = JSON.parse(raw);

            return parsed && typeof parsed === 'object' ? parsed : null;

        } catch (error) {

            console.error('Failed to parse resend payload', error);

            return null;

        }

    }



    function updatePopupPhone(popupElement, phone) {

        if (!popupElement) {

            return;

        }



        popupElement.dataset.phone = phone || '';

        const hiddenPhoneInput = popupElement.querySelector('input[name="phone"]');

        if (hiddenPhoneInput) {

            hiddenPhoneInput.value = phone || '';

        }

    }



    function resolvePopupComponent(popupElement) {

        if (!popupElement) {

            return null;

        }



        if (popupComponentMap.has(popupElement.id)) {

            return popupComponentMap.get(popupElement.id);

        }



        if (popupElement.dataset.componentName) {

            return popupElement.dataset.componentName;

        }



        if (popupElement.id === 'popup1') {

            return 'brakes:auth.register';

        }



        if (popupElement.id === 'popup3') {

            return 'brakes:auth.login';

        }



        return null;

    }

    function redirectAfterAuth(url = "/") {
        window.location.href = url;
    }

    /**
     * Handles the AJAX response for code verification.
     * @param {object} response - The response from the server.
     * @param {string} component - The name of the Bitrix component ('brakes:auth.register' or 'brakes:auth.login').
     */
    function handleVerifyResponse(response, component, popupId) {
        const activePopup = document.getElementById(popupId);
        if (!activePopup) {
            console.error(`handleVerifyResponse: Popup with ID #${popupId} not found.`);
            // Fallback alert if something is terribly wrong
            alert(response.data.message || 'Произошла ошибка.');
            return;
        }

        const codeInput = activePopup.querySelector('input[name="code"]');

        // The new showError function will handle clearing previous errors.

        if (response.data.status === 'success') {
            if (component === 'brakes:auth.register') {
                // Logic for successful registration...
                const popupContent = activePopup.querySelector('[data-fls-popup-content]');
                const userId = response.data.userId;
                if (popupContent && userId) {
                    if (document.activeElement && activePopup.contains(document.activeElement)) {
                        document.activeElement.blur();
                    }
                    const successHTML = `
                        <div class="popup__login-signin login__signin">
                            <h1 class="popup__main-title">Вы успешно зарегистрировались</h1>
                            <p class="popup__login-message">Нажмите "Войти", чтобы продолжить.</p>
                            <div class="popup__login-success-actions" style="margin-top: 20px;">
                                <button id="loginAfterRegBtn" class="popup__login-success-btn contacts-form-btn main-cataloge__shoping-btn">
                                    <span class="main-cataloge__shoping-text">Войти</span>
                                </button>
                            </div>
                        </div>`;
                    popupContent.innerHTML = successHTML;
                    const loginBtn = document.getElementById('loginAfterRegBtn');
                    loginBtn.addEventListener('click', function() {
                        BX.ajax.runComponentAction('brakes:auth.register', 'loginUserById', {
                            mode: 'class',
                            data: { userId: userId },
                        }).then(function(loginResponse) {
                            if (loginResponse.data.status === 'success') {
                                redirectAfterAuth('/');
                            } else {
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'form-error-message server-error-message';
                                errorDiv.style.color = 'red';
                                errorDiv.style.marginTop = '10px';
                                errorDiv.textContent = loginResponse.data.message || 'Ошибка входа.';
                                loginBtn.after(errorDiv);
                            }
                        }).catch(function(error) {
                            console.error('Login after registration failed:', error);
                        });
                    });
                } else {
                    redirectAfterAuth('/');
                }
            } else {
                // For a successful login, show success message before redirect.
                const popupContent = activePopup.querySelector('[data-fls-popup-content]');
                if (popupContent) {
                    if (document.activeElement && activePopup.contains(document.activeElement)) {
                        document.activeElement.blur();
                    }
                    const successHTML = `
                        <div class="popup__login-signin login__signin">
                            <h1 class="popup__main-title">Вы успешно вошли</h1>
                            <p class="popup__login-message">Для продолжения нажмите "ОК".</p>
                            <div class="popup__login-success-actions" style="margin-top: 20px;">
                                <button id="loginSuccessOkBtn" class="popup__login-success-btn contacts-form-btn main-cataloge__shoping-btn">
                                    <span class="main-cataloge__shoping-text">ОК</span>
                                </button>
                            </div>
                        </div>`;
                    popupContent.innerHTML = successHTML;
                    document.getElementById('loginSuccessOkBtn').addEventListener('click', () => {
                        redirectAfterAuth('/');
                    });
                } else {
                    // Fallback if content area is not found
                    redirectAfterAuth('/');
                }
            }
        } else {
            // Use showError for consistent error placement
            if (codeInput) {
                // Add a specific class for server errors to distinguish them
                showError(codeInput, response.data.message || 'Произошла ошибка.', 'server-error-message');
            } else {
                alert(response.data.message || 'Произошла ошибка.');
            }
        }
    }

    /**
     * Attaches a click handler to the code verification button inside a popup.
     * @param {HTMLElement} popupElement - The popup element.
     */
    function attachVerifyHandler(popupElement) {

        if (!popupElement) {

            return;

        }



        const popupId = popupElement.id;

        const resolvedComponent = resolvePopupComponent(popupElement);



        if (resolvedComponent) {

            popupElement.dataset.componentName = resolvedComponent;

        }



        const verifyBtn = popupElement.querySelector('.js-verify-code-btn');



        if (verifyBtn && !verifyBtn.dataset.handlerAttached) {

            verifyBtn.addEventListener('click', function (e) {

                e.preventDefault();



                const codeInput = popupElement.querySelector('input[name="code"]');

                const phone = popupElement.dataset.phone;

                const code = codeInput ? codeInput.value : '';



                if (!code || !phone) {

                    const input = popupElement.querySelector('input[name="code"]');

                    showError(input, 'Введите код подтверждения');

                    return;

                }



                const componentName = popupElement.dataset.componentName || resolvePopupComponent(popupElement);



                if (!componentName) {

                    console.warn('Component name is not defined for popup', popupId);

                    return;

                }



                BX.ajax.runComponentAction(componentName, 'verifyCode', {

                    mode: 'class',

                    data: { code: code, phone: phone },

                }).then(response => handleVerifyResponse(response, componentName, popupId))

                .catch(function(error) {

                    console.error('Verification code submission failed:', error);

                });

            });



            verifyBtn.dataset.handlerAttached = 'true';

        }



        const resendBtn = popupElement.querySelector('.js-resend-code-btn');



        if (resendBtn && !resendBtn.dataset.handlerAttached) {

            ensureResendDefaultText(resendBtn);



            resendBtn.addEventListener('click', function (e) {

                e.preventDefault();



                const componentName = popupElement.dataset.componentName || resolvePopupComponent(popupElement);



                if (!componentName) {

                    console.warn('Component name is not defined for resend operation.');

                    return;

                }



                if (isResendOnCooldown(popupElement)) {

                    return;

                }



                const payload = getResendPayload(popupElement);



                if (!payload) {

                    console.warn('Resend payload is missing for popup', popupId);

                    alert('Не удалось повторно отправить код. Попробуйте запросить код заново.');

                    return;

                }



                toggleResendButtonLoading(resendBtn, true, popupElement);



                BX.ajax.runComponentAction(componentName, 'sendCode', {

                    mode: 'class',

                    data: payload,

                }).then(response => {

                    if (response?.data?.status === 'success') {

                        if (payload.phone) {

                            updatePopupPhone(popupElement, payload.phone);

                        }



                        const nextAvailableAt = Date.now() + RESEND_DELAY_MS;

                        setResendCooldown(popupElement, nextAvailableAt);

                    } else {

                        const message = response?.data?.message || 'Не удалось отправить код повторно.';

                        const codeInput = popupElement.querySelector('input[name="code"]');



                        if (codeInput) {

                            showError(codeInput, message, 'server-error-message');

                        } else {

                            alert(message);

                        }



                        toggleResendButtonLoading(resendBtn, false, popupElement);

                    }

                }).catch(function(error) {

                    console.error('Send code failed:', error);

                    toggleResendButtonLoading(resendBtn, false, popupElement);

                });

            });



            resendBtn.dataset.handlerAttached = 'true';

        }



        restoreResendState(popupElement);

    }

    /**
     * Handles the AJAX response for sending a confirmation code.
     * @param {object} response - The response from the server.
     * @param {string} popupId - The ID of the popup to open.
     * @param {HTMLFormElement} form - The form that was submitted.
     */
    function handleSendCodeResponse(response, popupId, form) {

        if (!form) {

            return;

        }



        const existingError = form.querySelector('.server-error-message');

        if (existingError) {

            existingError.remove();

        }



        if (response.data.status === 'success') {

            const payload = extractPayloadFromForm(form);



            if (!window.flsPopup) {

                console.error('auth.js: Popup manager (window.flsPopup) is not defined!');

                alert('Не удалось открыть окно подтверждения. Попробуйте позже.');

                return;

            }



            window.flsPopup.open(popupId);



            const popup = document.getElementById(popupId);



            if (popup) {

                const componentName = popupComponentMap.get(popupId);



                if (componentName) {

                    popup.dataset.componentName = componentName;

                }



                updatePopupPhone(popup, payload.phone || '');

                storeResendPayload(popup, payload);



                const nextAvailableAt = Date.now() + RESEND_DELAY_MS;

                setResendCooldown(popup, nextAvailableAt);

            } else {

                console.warn(`Popup with ID #${popupId} not found after opening.`);

            }

        } else {

            const submitButton = form.querySelector('button[type="submit"]');



            if (submitButton) {

                const errorDiv = document.createElement('div');



                errorDiv.className = 'form-error-message server-error-message';

                errorDiv.style.fontWeight = 'bold';

                errorDiv.style.color = 'red';

                errorDiv.style.marginBottom = '15px';

                errorDiv.textContent = response.data.message || 'Не удалось отправить код.';



                submitButton.before(errorDiv);

            } else {

                alert(response.data.message || 'Не удалось отправить код.');

            }

        }

    }

    /**
     * Validates the form fields.
     * @param {HTMLFormElement} form - The form to validate.
     * @returns {boolean} - True if the form is valid, false otherwise.
     */
    function validateForm(form) {
        let isValid = true;
        
        // Clear previous errors
        form.querySelectorAll('.form-error-message').forEach(error => error.remove());
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

        const inputs = form.querySelectorAll('input[name="name"], input[name="phone"], input[name="email"]');
        
        inputs.forEach(input => {
            const value = input.value.trim();
            let hasError = false;

            if (!value) {
                hasError = true;
                showError(input, 'Заполните поле');
            } else if (input.name === 'email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    hasError = true;
                    showError(input, 'Введите корректный email');
                }
            } else if (input.name === 'phone') {
                const phoneRegex = /^(\+7|8|7)?[\s\-\(]*([0-9]{3})[\s\-\)]*([0-9]{3})[\s\-]*([0-9]{2})[\s\-]*([0-9]{2})$/;
                if (!phoneRegex.test(value)) {
                    hasError = true;
                    showError(input, 'Введите корректный номер телефона');
                }
            }

            if (hasError) {
                isValid = false;
            }
        });

        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = !isValid;
        }

        return isValid;
    }

    /**
     * Displays a validation error message for a given input.
     * @param {HTMLInputElement} input - The input element with an error.
     * @param {string} message - The error message to display.
     */
    function showError(input, message, customClass = '') {
        const wrapper = input.closest('.login__input-wrapper, .contacts-form__input-wrapper');
        if (wrapper) {
            // Remove the next sibling if it's an error message, ensuring no duplicates.
            const nextEl = wrapper.nextElementSibling;
            if (nextEl && (nextEl.classList.contains('form-error-message') || nextEl.classList.contains('server-error-message'))) {
                nextEl.remove();
            }

            wrapper.classList.add('error');
            const errorDiv = document.createElement('div');
            errorDiv.className = `form-error-message ${customClass}`.trim();
            errorDiv.textContent = message;
            wrapper.after(errorDiv);
        }
    }

    /**
     * Initializes a form with submit and validation handlers.
     * @param {string} formId - The ID of the form to initialize.
     * @param {string} component - The name of the Bitrix component.
     * @param {string} popupId - The ID of the popup to open on success.
     */
    function initForm(formId, component, popupId) {

        const form = document.getElementById(formId);

        if (form) {

            popupComponentMap.set(popupId, component);



            const submitButton = form.querySelector('button[type="submit"]');

            if (submitButton) {

                submitButton.disabled = true;

            }



            form.addEventListener('input', () => validateForm(form));

            form.addEventListener('submit', function (e) {

                e.preventDefault();

                if (validateForm(form)) {

                    BX.ajax.runComponentAction(component, 'sendCode', {

                        mode: 'class',

                        data: new FormData(form),

                    }).then(response => handleSendCodeResponse(response, popupId, form))

                    .catch(function(error) {

                        console.error('Send code failed:', error);

                    });

                }

            });

        } else {

            popupComponentMap.delete(popupId);

        }

    }

    // --- Main Execution ---

    // Initialize forms for registration and login
    initForm('reg-form', 'brakes:auth.register', 'popup1');
    initForm('login-form', 'brakes:auth.login', 'popup3');

    // Set up a MutationObserver to attach handlers to popups when they open
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            if (mutation.attributeName === 'aria-hidden') {
                const popup = mutation.target;
                if (popup.classList.contains('popup') && popup.getAttribute('aria-hidden') === 'false') {
                    // It's a popup that has just been opened.
                    attachVerifyHandler(popup);

                    // Clear all previous error messages and input values
                    popup.querySelectorAll('.form-error-message, .server-error-message').forEach(err => err.remove());
                    popup.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
                    
                    const codeInput = popup.querySelector('input[name="code"]');
                    if (codeInput) {
                        codeInput.value = ''; // Reset code input
                        
                        // Add a one-time listener to clear errors on new input
                        if (!codeInput.dataset.inputHandlerAttached) {
                             codeInput.addEventListener('input', () => {
                                const wrapper = codeInput.closest('.login__input-wrapper, .contacts-form__input-wrapper');
                                if (wrapper) {
                                    wrapper.classList.remove('error');
                                    const nextEl = wrapper.nextElementSibling;
                                    if (nextEl && (nextEl.classList.contains('form-error-message') || nextEl.classList.contains('server-error-message'))) {
                                        nextEl.remove();
                                    }
                                }
                            }, { once: false }); // Use once:false if you want it to trigger every time
                            codeInput.dataset.inputHandlerAttached = 'true';
                        }
                    }
                }
            }
        });
    });

    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['aria-hidden'],
        subtree: true
    });

});