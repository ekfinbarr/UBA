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
                            if (
                                method.method_code === 'door' ||
                                method.method_code === 'evening' ||
                                method.method_code === 'no_neighbour' ||
                                method.method_code === 'no_neighbour_evening'
                            ) {
                                if (window.uba_dhlexpress_deliverytimes_enabled === true) {
                                    if (quote.shippingAddress().countryId === 'NL') {
                                        if (window.uba_dhlexpress_deliverytimes_validated !== true) {
                                            $('#uba-dhlexpress-deliverytimes-info-error').show();
                                            return false;
                                        }
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
