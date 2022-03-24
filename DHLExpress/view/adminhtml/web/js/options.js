require([
    "jquery",
    "jquery/ui"
], function ($) {

    $.widget('dhlexpress.optionsform', {
        options: {
            container: null,
            enableCheckbox: null,
            baseUrl: '',
            audienceState: '',
            capabilitiesData: {},
            initialized: false
        },

        _create: function () {
            this.options.container = this.element
            this.options.enableCheckbox = $('#create_uba_dhlexpress_label')
            this.options.baseUrl = this.options.container.attr('data-url-base')
            this._updateState($('.dhlexpress-audience-selector:checked', container))
            this._bind()
        },

        _bind: function () {
            let self = this
            $('.dhlexpress-audience-selector', this.options.container).change(function () {
                self._updateState(this)
            })
            $('.dhlexpress-service-option input,.dhlexpress-delivery-option input', this.options.container).change(function () {
                self._updateExceptions()
            })
            $('#test_button').click(function () {
                self._debug()
            })
            $(this.options.enableCheckbox).change(function () {
                if ($(this).prop('checked')) {
                    $('#dhlexpress-options-container').show();
                    $('#dhlexpress-options-container .dhlexpress-package-selection').prop('required',true)
                    $('#dhlexpress-options-container .dhlexpress-service-option input').each(function () {
                        $(this).prop('disabled', false)
                    })
                    self._updateExceptions()
                } else {
                    $('#dhlexpress-options-container').hide();
                    $('#dhlexpress-options-container .dhlexpress-package-selection').prop('required',false)
                    $('#dhlexpress-options-container .dhlexpress-service-option input').each(function () {
                        $(this).prop('disabled', true)
                    })
                }
            })

            $('.dhlexpress-remove-package').click(function() {
                $(this).closest('.dhlexpress-package').remove()
            })

            $('.dhlexpress-add-package').click(function() {
                $(this).closest('.dhlexpress-package').clone(true, true).appendTo('.dhlexpress-packages')
            })
        },

        _capabilities: function (attribute = null) {
            if (this.options.capabilitiesData[this.options.audienceState]) {
                let capabilities = this.options.capabilitiesData[this.options.audienceState];
                switch (attribute) {
                    case 'options':
                        return capabilities.options
                    case 'products':
                        return capabilities.products
                    default:
                        return capabilities
                }
            } else {
                return false;
            }
        },

        _updateState: function (element) {
            this.options.audienceState = $(element).val()
            if (!this._capabilities()) {
                let self = this
                $.get(this.options.baseUrl + this.options.audienceState, function (data) {
                    self.options.capabilitiesData[self.options.audienceState] = data
                    self._updateElements()
                    if(!self.options.initialized){
                        self.options.initialized = true;
                        $(self.options.enableCheckbox).trigger('change')
                    }
                })
            } else {
                this._updateElements()
            }
        },

        _updateElements: function () {
            let capabilities = this._capabilities('options');
            $('.dhlexpress-delivery-option, .dhlexpress-service-option', this.options.container).each(function () {
                if (capabilities[$(this).attr('data-option')]) {
                    $(this).removeClass('unavailable-option')
                } else {
                    $(this).addClass('unavailable-option')
                    $('input:checked', this).prop('checked', false)
                }
            })
            this._updateExceptions()
        },

        _updateExceptions: function () {
            if (this._capabilities()) {
                let capabilities = this._capabilities('options')
                let exclusions = []
                let packages = this._capabilities('products')
                let self = this
                this._updatePackages()
                $('.dhlexpress-service-option input:checked,.dhlexpress-delivery-option input:checked', this.options.container).each(function () {
                    let option = $(this).prop('value')
                    $.each(capabilities[option].exclusions, function (key, value) {
                        if (!exclusions.includes(value)) {
                            exclusions.push(value)
                        }
                    })
                    $.each(packages, function (key) {
                        if (!capabilities[option].type.includes(key)) {
                            $('.dhlexpress-package-selection option[value="' + key + '"]', self.options.container).remove()
                        }
                    })
                })
                $('.dhlexpress-service-option').each(function () {
                    if (exclusions.includes($(this).attr('data-option'))) {
                        $('input.dhlexpress-service-option', this).prop('checked', false).prop('disabled', true)
                    } else {
                        $('input.dhlexpress-service-option', this).prop('disabled', false)
                    }
                })
                $('input, select','.dhlexpress-step-container .dhlexpress-delivery-options-data').hide();
                $.each($('.dhlexpress-step-container .dhlexpress-delivery-options input[type="radio"]:checked'),function (key,element) {
                    $('.dhlexpress-step-container .dhlexpress-delivery-options-data [data-method="'+$(element).val()+'"]').show();
                });
            }
        },

        _updatePackages: function () {
            let options = '';
            $.each(this._capabilities('products'), function (key, product) {
                options += '<option value="' + product.key + '">' +
                    product.key + ' ' + product.minWeightKg + 'KG - ' + product.maxWeightKg + 'KG (max L' +
                    product.dimensions.maxLengthCm + ' W' + product.dimensions.maxWidthCm + ' H' + product.dimensions.maxHeightCm +
                    ' cm)</option>'
            })

            $('.dhlexpress-package-selection', this.options.container).each(function(){
                let selectedValue = $('option:selected', $(this)).val()

                $(this).html(options)

                if (typeof selectedValue !== 'undefined') {
                    $('option[value="' + selectedValue + '"]', this).attr('selected',' selected')
                }
            })
        },

        _debug: function () {
            console.log(this.options)
        },
    });

    $('#dhlexpress-options-container').optionsform();
});
