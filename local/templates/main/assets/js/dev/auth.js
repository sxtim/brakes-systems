window.addEventListener("load", function () {
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
                                window.location.href = '/';
                            } else {
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'form-error-message server-error-message';
                                errorDiv.style.color = 'red';
                                errorDiv.style.marginTop = '10px';
                                errorDiv.textContent = loginResponse.data.message || 'Ошибка входа.';
                                loginBtn.after(errorDiv);
                            }
                        });
                    });
                } else {
                    window.location.href = '/';
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
                        window.location.href = '/';
                    });
                } else {
                    // Fallback if content area is not found
                    window.location.href = '/';
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
        if (!popupElement) return;

        const verifyBtn = popupElement.querySelector('.js-verify-code-btn');
        if (verifyBtn && !verifyBtn.dataset.handlerAttached) {
            verifyBtn.addEventListener('click', function (e) {
                e.preventDefault();

                const codeInput = popupElement.querySelector('input[name="code"]');
                const phone = popupElement.dataset.phone; // Get phone from dataset for reliability
                const code = codeInput ? codeInput.value : '';
                const component = popupElement.id === 'popup1' ? 'brakes:auth.register' : 'brakes:auth.login';
                const popupId = popupElement.id;

                if (!code || !phone) {
                    const input = popupElement.querySelector('input[name="code"]');
                    showError(input, 'Введите код подтверждения');
                    return;
                }

                BX.ajax.runComponentAction(component, 'verifyCode', {
                    mode: 'class',
                    data: { code: code, phone: phone },
                }).then(response => handleVerifyResponse(response, component, popupId));
            });
            verifyBtn.dataset.handlerAttached = 'true';
        }
    }

    /**
     * Handles the AJAX response for sending a confirmation code.
     * @param {object} response - The response from the server.
     * @param {string} popupId - The ID of the popup to open.
     * @param {HTMLFormElement} form - The form that was submitted.
     */
    function handleSendCodeResponse(response, popupId, form) {
        if (!form) return;

        // Remove any existing server error message
        const existingError = form.querySelector('.server-error-message');
        if (existingError) {
            existingError.remove();
        }

        if (response.data.status === 'success') {
            if (window.flsPopup) {
                window.flsPopup.open(popupId);
                const popup = document.getElementById(popupId);
                if (popup) {
                    const formPhone = form.querySelector('input[name="phone"]').value;
                    popup.dataset.phone = formPhone;
                }
            } else {
                console.error('auth.js: Popup manager (window.flsPopup) is not defined!');
                alert('Код подтверждения отправлен на ваш номер.');
            }
        } else {
            // Create and display a new server error message
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'form-error-message server-error-message';
                errorDiv.style.fontWeight = 'bold';
                errorDiv.style.color = 'red';
                errorDiv.style.marginBottom = '15px';
                errorDiv.textContent = response.data.message || 'Произошла ошибка.';
                submitButton.before(errorDiv);
            } else {
                // Fallback if the submit button isn't found for some reason
                alert(response.data.message || 'Произошла ошибка.');
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
                    }).then(response => handleSendCodeResponse(response, popupId, form));
                }
            });
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