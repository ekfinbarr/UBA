define([
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        'UBA_DHLExpress/js/model/dhlexpress-validator',
        'UBA_DHLExpress/js/model/dhlexpress-rules'
    ], function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        shippingRatesValidator,
        shippingRatesValidationRules
    ) {
        'use strict';
        defaultShippingRatesValidator.registerValidator('dhlexpress', shippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('dhlexpress', shippingRatesValidationRules);
        return Component;
    }
);
