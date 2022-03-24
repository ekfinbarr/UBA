define([
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote'
    ], function ($, ko, quote) {
        'use strict';

        return function (shippingAction) {
            return shippingAction.extend({
                errorDeliveryValidationMessage: ko.observable(false),
                validateShippingInformation: function () {
                    var method = quote.shippingMethod();
                    if (typeof method !== 'undefined' && method !== null && typeof method.carrier_code !== 'undefined' && typeof method.method_code !== 'undefined') {
                        if (method.carrier_code === 'dhlexpress') {
                            if (method.method_code === 'door') {
                                if (window.uba_dhlexpress_deliveryservices_enabled === true) {
                                    if (window.uba_dhlexpress_services_current_request_sequence_validation !== window.uba_dhlexpress_services_current_request_sequence) {
                                        $('#uba-dhlexpress-deliveryservices-info-error').show();
                                        return false;
                                    }
                                }
                            }
                        }
                    }

                    return this._super();
                }
            });
        }
    }
);
