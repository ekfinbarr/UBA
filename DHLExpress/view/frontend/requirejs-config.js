var config = {
    map: {
        '*': {
            'uba-dhlexpress-servicepoint': 'UBA_DHLExpress/js/view/servicepoint-loader'
        }
    },
    'config': {
        'mixins': {
            'Magento_Checkout/js/view/shipping': {
                'UBA_DHLExpress/js/view/servicepoint-validate-mixin': true,
                'UBA_DHLExpress/js/view/deliverytimes-validate-mixin': true,
                'UBA_DHLExpress/js/view/deliveryservices-validate-mixin': true
            }
        }
    }
};
