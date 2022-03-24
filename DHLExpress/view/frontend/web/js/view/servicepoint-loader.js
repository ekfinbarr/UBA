define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Ui/js/modal/modal',
    'mage/url',
    'Magento_Checkout/js/model/shipping-rate-processor/new-address',
    'Magento_Checkout/js/model/shipping-rate-processor/customer-address'
], function($, quote, rateRegistry, modal, urlBuilder, defaultProcessor, customerAddressProcessor) {
    return function(config, element) {
        var uba_dhlexpress_servicepoint_modal_loading_busy = false;
        var uba_dhlexpress_servicepoint_modal_loaded = false;
        var uba_dhlexpress_servicepoint_selected = false;

        $(document.body).on('uba_dhlexpress:load_servicepoint_modal', function(e) {
            if (uba_dhlexpress_servicepoint_modal_loaded === true) {
                return;
            }

            /* Prevent loading additional times while loading by checking if it's busy loading */
            if (uba_dhlexpress_servicepoint_modal_loading_busy === true) {
                return;
            }

            uba_dhlexpress_servicepoint_modal_loading_busy = true;

            $.post(urlBuilder.build('dhlparcel_shipping/servicepoint/content'), {}, function (response) {
                try {
                    var view = response.data.view;
                } catch (error) {
                    console.log(error);
                    return;
                }

                $(document.body).append(view);

                /* Init modal */
                $('#uba-dhlexpress-modal-content').modal({
                    modalClass: 'uba-dhlexpress-modal',
                    buttons: []
                });

                // Create selection function
                window.uba_dhlexpress_select_servicepoint = function(event)
                {
                    event.additional_servicepoint_id = null;
                    $(document.body).trigger("uba_dhlexpress:servicepoint_selection", [event.id, event.address.countryCode, event.name]);

                    $('#uba-dhlexpress-modal-content').modal('closeModal');
                };

                // Disable getScript from adding a custom timestamp
                // $.ajaxSetup({cache: true});
                $.getScript("https://static.dhlparcel.nl/components/servicepoint-locator-component@latest/servicepoint-locator-component.js").done(function() {
                    // Load ServicePoint Locator
                    var configElement = $('#dhl-servicepoint-locator-component');
                    var limit = $(configElement).attr('data-limit');
                    var apiKey = $(configElement).attr('data-maps-key');
                    var locale = $(configElement).attr('data-locale');

                    var options = {
                        language: locale,
                        country: '',
                        limit: limit,
                        header: true,
                        resizable: true,
                        onSelect: window.uba_dhlexpress_select_servicepoint
                    };

                    if (apiKey.length > 0) {
                        options.googleMapsApiKey = apiKey;
                    }

                    window.uba_dhlexpress_servicepoint_locator = new dhl.servicepoint.Locator(document.getElementById("dhl-servicepoint-locator-component"), options);

                    uba_dhlexpress_servicepoint_modal_loaded = true;
                    uba_dhlexpress_servicepoint_modal_loading_busy = false;
                });

            }, 'json');

        }).on('uba_dhlexpress:show_servicepoint_modal', function(e) {
            // Do nothing if the base modal hasn't been loaded yet.
            if (uba_dhlexpress_servicepoint_modal_loaded !== true) {
                console.log('An unexpected error occured. ServicePoint component is not loaded yet.');
                return;
            }

            var countryId = quote.shippingAddress().countryId;
            var postcode = quote.shippingAddress().postcode;

            if (typeof window.uba_dhlexpress_servicepoint_locator !== 'undefined') {
                window.uba_dhlexpress_servicepoint_locator.setCountry(countryId)
                window.uba_dhlexpress_servicepoint_locator.setQuery(postcode)
            }

            $('#uba-dhlexpress-modal-content').modal('openModal').on('modalclosed', function () {
                $(document.body).trigger('uba_dhlexpress:check_servicepoint_selection');
            });

        }).on('uba_dhlexpress:check_servicepoint_selection', function(e) {
            if (uba_dhlexpress_servicepoint_selected != true) {
                // No servicepoint is selected, deselect shipping method
                quote.shippingMethod(null);
            }

        }).on('uba_dhlexpress:servicepoint_selection', function(e, servicepoint_id, servicepoint_country, servicepoint_name) {
            if (servicepoint_id == null) {
                uba_dhlexpress_servicepoint_selected = false;
                return;
            }

            uba_dhlexpress_servicepoint_selected = true;
            var data = {
                'servicepoint_id': servicepoint_id,
                'servicepoint_country': servicepoint_country,
                'servicepoint_name': servicepoint_name
            };

            if (typeof quote.shippingAddress() !== 'undefined' && quote.shippingAddress() !== null) {
                data.servicepoint_postcode = quote.shippingAddress().postcode;
            }

            $.post(urlBuilder.build('dhlparcel_shipping/servicepoint/sync'), data, function (response) {
                if (typeof quote.shippingAddress() === 'undefined' || quote.shippingAddress() === null) {
                    return;
                }
                /* Update methods */
                var processors = [];
                rateRegistry.set(quote.shippingAddress().getCacheKey(), null);
                processors.default =  defaultProcessor;
                processors['customer-address'] = customerAddressProcessor;
                var type = quote.shippingAddress().getType();
                if (processors[type]) {
                    processors[type].getRates(quote.shippingAddress());
                } else {
                    processors.default.getRates(quote.shippingAddress());
                }

                try {
                    var data = response.data;
                } catch (error) {
                    console.log(error);
                    return;
                }
                window.uba_dhlexpress_servicepoint_validate = data;
                $('#uba-dhlexpress-servicepoint-info-error').hide();
            });

        }).on('click', '#uba-dhlexpress-servicepoint-button', function(e) {
            e.preventDefault();
            $(document.body).trigger('uba_dhlexpress:show_servicepoint_modal');

        }).on('uba_dhlexpress:servicepoint_validate', function(e) {
            $.post(urlBuilder.build('dhlparcel_shipping/servicepoint/validate'), function (response) {
                try {
                    var data = response.data;
                } catch (error) {
                    var data = false

                    console.log(error);
                    return;
                }
                window.uba_dhlexpress_servicepoint_validate = data;

                $(document.body).trigger('uba_dhlexpress:servicepoint_validated', [ data ]);

                $('#uba-dhlexpress-servicepoint-info-error').hide();
            });

        });

        // Preload modal, since it's loaded dynamically (hidden DOM defaults)
        $(document.body).trigger('uba_dhlexpress:load_servicepoint_modal');

        // Save shipping method to global
        quote.shippingMethod.subscribe(function(method) {
            if (typeof method === 'undefined' || method === null || typeof method.carrier_code === 'undefined' || typeof method.method_code === 'undefined') {
                return;
            }

            if (typeof quote.shippingAddress() === 'undefined' || quote.shippingAddress() === null) {
                return;
            }

            var method_name_check = method.carrier_code + '_' + method.method_code;
            var method_full_name_check =  method_name_check + '_' + quote.shippingAddress().postcode + '_' + quote.shippingAddress().countryId;

            // Added a memory check, due to a double firing bug of Magento2 of this event
            if (window.uba_dhlexpress_selected_shipping_method === method_full_name_check) {
                return;
            }

            if (method_name_check === 'uba_dhlexpress_servicepoint') {
                $(document.body).trigger('uba_dhlexpress:servicepoint_validate');
            }

            window.uba_dhlexpress_selected_shipping_method = method_full_name_check;

        }, null, 'change');

        // Always validate just to be sure the servicepoint validation is not out of sync
        $(document.body).trigger('uba_dhlexpress:servicepoint_validate');

    };
});
