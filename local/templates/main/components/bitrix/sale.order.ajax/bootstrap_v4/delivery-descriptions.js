(function () {
    'use strict';

    if (!window.BX)
        return;

    var descriptionClass = 'bx-soa-delivery-custom-description';
    var selectedDescriptionClass = 'bx-soa-delivery-selected-description';

    function getComponent()
    {
        return window.BX && BX.Sale && BX.Sale.OrderAjaxComponent;
    }

    function getDeliveryById(component, deliveryId)
    {
        var deliveries = component.result && component.result.DELIVERY;

        if (!deliveries)
            return null;

        for (var i = 0; i < deliveries.length; i++) {
            if (parseInt(deliveries[i].ID, 10) === deliveryId) {
                return deliveries[i];
            }
        }

        return null;
    }

    function getDescription(delivery)
    {
        return delivery && delivery.DESCRIPTION ? String(delivery.DESCRIPTION).trim() : '';
    }

    function renderDeliveryDescriptions()
    {
        var component = getComponent();
        var deliveryBlock = document.getElementById('bx-soa-delivery');

        if (!component || !deliveryBlock)
            return;

        var deliveryNodes = deliveryBlock.querySelectorAll('.bx-soa-pp-item-container .bx-soa-pp-company');

        for (var i = 0; i < deliveryNodes.length; i++) {
            var node = deliveryNodes[i];
            var oldDescription = node.querySelector('.' + descriptionClass);

            if (oldDescription) {
                oldDescription.parentNode.removeChild(oldDescription);
            }

            var input = node.querySelector('input[name="DELIVERY_ID"]');

            if (!input)
                continue;

            var delivery = getDeliveryById(component, parseInt(input.value, 10));
            var description = getDescription(delivery);

            if (!description)
                continue;

            var descriptionNode = document.createElement('div');
            descriptionNode.className = descriptionClass;
            descriptionNode.innerHTML = description;
            node.appendChild(descriptionNode);
        }
    }

    function renderSelectedDeliveryDescription()
    {
        var component = getComponent();
        var deliveryBlock = document.getElementById('bx-soa-delivery');

        if (!component || !deliveryBlock || typeof component.getSelectedDelivery !== 'function')
            return;

        var selectedNode = deliveryBlock.querySelector('.bx-soa-pp-company-selected');
        var oldDescription = deliveryBlock.querySelector('.' + selectedDescriptionClass);

        if (oldDescription) {
            oldDescription.parentNode.removeChild(oldDescription);
        }

        if (!selectedNode)
            return;

        var delivery = component.getSelectedDelivery();
        var description = getDescription(delivery);

        if (!description)
            return;

        var descriptionNode = document.createElement('div');
        descriptionNode.className = selectedDescriptionClass;
        descriptionNode.innerHTML = description;
        selectedNode.appendChild(descriptionNode);
    }

    function renderDescriptions()
    {
        renderDeliveryDescriptions();
        renderSelectedDeliveryDescription();
    }

    function wrapRenderMethod(component, methodName)
    {
        var original = component[methodName];

        if (typeof original !== 'function' || original.__deliveryDescriptionWrapped)
            return;

        component[methodName] = function () {
            var result = original.apply(this, arguments);
            renderDescriptions();

            return result;
        };
        component[methodName].__deliveryDescriptionWrapped = true;
    }

    BX.ready(function () {
        var component = getComponent();

        if (!component)
            return;

        wrapRenderMethod(component, 'editDeliveryItems');
        wrapRenderMethod(component, 'editActiveDeliveryBlock');
        wrapRenderMethod(component, 'editFadeDeliveryContent');
        wrapRenderMethod(component, 'editDeliveryBlock');

        renderDescriptions();
    });
})();
