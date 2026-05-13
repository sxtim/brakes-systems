(function () {
    'use strict';

    var awaitingPaymentStatus = 'AP';
    var attemptsLeft = 40;
    var retryDelay = 100;

    function patchSaveStatusRequest() {
        if (
            !window.BX
            || !BX.Sale
            || !BX.Sale.Admin
            || !BX.Sale.Admin.OrderEditPage
            || !BX.Sale.Admin.OrderEditPage.ajaxRequests
            || typeof BX.Sale.Admin.OrderEditPage.ajaxRequests.saveStatus !== 'function'
        ) {
            if (attemptsLeft > 0) {
                attemptsLeft -= 1;
                window.setTimeout(patchSaveStatusRequest, retryDelay);
            }
            return;
        }

        var requests = BX.Sale.Admin.OrderEditPage.ajaxRequests;
        if (requests.saveStatus.__brakesPaymentLinkReloadPatched) {
            return;
        }

        var originalSaveStatus = requests.saveStatus;

        requests.saveStatus = function (orderId, selectId) {
            var request = originalSaveStatus.apply(this, arguments);
            if (!request) {
                return request;
            }

            var originalCallback = request && request.callback;

            request.callback = function (result) {
                var select = BX(selectId);
                var selectedStatus = select && typeof select.value !== 'undefined' ? select.value : '';
                var hasError = result && result.ERROR;

                if (typeof originalCallback === 'function') {
                    originalCallback.apply(this, arguments);
                }

                if (!hasError && selectedStatus === awaitingPaymentStatus) {
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 350);
                }
            };

            return request;
        };

        requests.saveStatus.__brakesPaymentLinkReloadPatched = true;
    }

    if (window.BX && typeof BX.ready === 'function') {
        BX.ready(patchSaveStatusRequest);
    } else {
        document.addEventListener('DOMContentLoaded', patchSaveStatusRequest);
    }
})();
