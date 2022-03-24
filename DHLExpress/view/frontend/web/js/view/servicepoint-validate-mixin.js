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
                        if (method.carrier_code === 'dhlexpress' && method.method_code === 'servicepoint') {
                            if (window.uba_dhlexpress_servicepoint_validate !== true) {
                                $('#uba-dhlexpress-servicepoint-info-error').show();
                                return false;
                            }
                        }
                    }

                    return this._super();
                }
            });
        }
    }
);
