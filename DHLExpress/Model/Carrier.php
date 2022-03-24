<?php

namespace UBA\DHLExpress\Model;

use UBA\DHLExpress\Logger\DebugLogger;
use Magento\Checkout\Model\Session as CheckoutSession;
use UBA\DHLExpress\Helper\Data;
use UBA\DHLExpress\Model\Api\Connector;
use UBA\DHLExpress\Model\Service\Capability as CapabilityService;
use UBA\DHLExpress\Model\Service\CartService;
use UBA\DHLExpress\Model\PieceFactory;
use UBA\DHLExpress\Model\ResourceModel\Piece as PieceResource;
use UBA\DHLExpress\Model\Service\DeliveryTimes as DeliveryTimesService;
use UBA\DHLExpress\Model\Service\DeliveryServices as DeliveryServicesService;
use UBA\DHLExpress\Model\ResourceModel\Carrier\RateManager;
use UBA\DHLExpress\Model\Service\ServicePoint as ServicePointService;

use UBA\DHLExpress\Model\Config\Source\RateConditions;
use UBA\DHLExpress\Model\Config\Source\RateMethod;


class Carrier extends \Magento\Shipping\Model\Carrier\AbstractCarrierOnline implements \Magento\Shipping\Model\Carrier\CarrierInterface
{
    // Attributes are restricted to Mage_Eav_Model_Entity_Attribute::ATTRIBUTE_CODE_MAX_LENGTH = 30 max characters
    const BLACKLIST_GENERAL = 'dhlexpress_blacklist';
    const BLACKLIST_SERVICEPOINT = 'dhlexpress_blacklist_sp';
    const BLACKLIST_ALL = 'dhlexpress_blacklist_all';
    protected $_code = 'dhlexpress';
    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $rateResultFactory;
    protected $helper;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $rateMethodFactory;

    protected $capabilityService;
    protected $cartService;
    protected $checkoutSession;
    protected $debugLogger;
    protected $defaultConditionName;
    protected $pieceFactory;
    protected $pieceResource;
    protected $deliveryTimesService;
    protected $deliveryServicesService;
    protected $rateManager;
    protected $servicePointService;
    protected $storeManager;
    protected $trackingUrl = 'https://www.dhlparcel.nl/nl/volg-uw-zending?tc={{trackerCode}}&pc={{postalCode}}';
    protected $connector;
    protected $_curl;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Xml\Security $xmlSecurity,
        \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        CapabilityService $capabilityService,
        CartService $cartService,
        DebugLogger $debugLogger,
        PieceFactory $pieceFactory,
        PieceResource $pieceResource,
        DeliveryTimesService $deliveryTimesService,
        DeliveryServicesService $deliveryServicesService,
        RateManager $rateManager,
        ServicePointService $servicePointService,
        Connector $connector,
        Data $helper,
        \Magento\Framework\HTTP\Client\Curl $curl,
        array $data = []
    ) {
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );

        $this->rateResultFactory = $rateResultFactory;;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->checkoutSession = $checkoutSession;
        $this->capabilityService = $capabilityService;
        $this->cartService = $cartService;
        $this->defaultConditionName = RateConditions::PACKAGE_VALUE;
        $this->debugLogger = $debugLogger;
        $this->pieceFactory = $pieceFactory;
        $this->pieceResource = $pieceResource;
        $this->deliveryTimesService = $deliveryTimesService;
        $this->deliveryServicesService = $deliveryServicesService;
        $this->rateManager = $rateManager;
        $this->servicePointService = $servicePointService;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->connector = $connector;
        $this->_curl = $curl;

        if ($this->getConfigData('label/alternative_tracking/enabled')) {
            $this->trackingUrl = $this->getConfigData('label/alternative_tracking/url');
        }
    }

    protected function _getStandardShippingRate()
    {
        if (!$this->getConfigFlag('shipping_methods/standard/enabled')) {
            return false;
        }
        $this->debugLogger->info('CARRIER ### _getStandardShippingRate');
        $rate = $this->_rateMethodFactory->create();

        $rate->setCarrier($this->getCarrierCode());
        $rate->setCarrierTitle($this->getConfigData('title'));

        $rate->setMethod('standard');
        $rate->setMethodTitle($this->getConfigData('shipping_methods/standard/title'));

        $rate->setPrice($this->getConfigData('shipping_methods/standard/price') ?? 0);
        $rate->setCost(0);

        return $rate;
    }

    protected function _getFreeShippingRate()
    {
        if (!$this->getConfigFlag('shipping_methods/free_shipping/enabled')) {
            return false;
        }
        $rate = $this->_rateMethodFactory->create();
        /* @var $rate Mage_Shipping_Model_Rate_Result_Method */
        $rate->setCarrier($this->getCarrierCode());
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod('free_shipping');
        $rate->setMethodTitle($this->getConfigData('shipping_methods/free_shipping/title'));
        $rate->setPrice($this->getConfigData('shipping_methods/free_shipping/price') ?? 0);
        $rate->setCost(0);
        return $rate;
    }

    protected function _getExpressShippingRate(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        if (!$this->getConfigFlag('shipping_methods/express/enabled')) {
            return false;
        }
        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateFactory->create();
        try {
            $blacklist = $this->createBlacklist($request->getAllItems());
            if ($blacklist === true) {
                return $result;
            }
            $currencyType = 'BILLC';

            // Destination address
            $destCountryId = $request->getDestCountryId();
            $destCountry = $request->getDestCountry();
            $destRegion = $request->getDestRegionId();
            $destRegionCode = $request->getDestRegionCode();
            $destFullStreet = $request->getDestStreet();
            $destStreet = $request->getDestStreet();
            $destSuburb = $request->getDestCity();
            $destCity = $request->getDestCity();
            $destPostcode = $request->getDestPostcode();

            // System address
            $fromCountry = $request->getCountryId() ?? $this->helper->getConfigData('shipper/country_code');
            $fromPostalCode = $request->getPostCode() ?? $this->helper->getConfigData('shipper/postal_code');
            $fromCity = $request->getCity() ?? $this->helper->getConfigData('shipper/city');
            $fromStreet = $this->helper->getConfigData('shipper/street');
            $fromRegion = $request->getRegionId() ?? null;

            // Account Numbers
            $exportAccountNumber = $this->helper->getConfigData('api/export_account');
            $importAccountNumber = $this->helper->getConfigData('api/import_account');

            // Shipment Details

            $accountNumber = '';
            if (trim($fromCountry) !== trim($destCountryId)) {
                $accountNumber = trim($exportAccountNumber);
            } else {
                $accountNumber = trim($importAccountNumber);
            }

            if ($destFullStreet != null && $destFullStreet != "") {
                $destFullStreetArray = explode("\n", $destFullStreet);
                $count = count($destFullStreetArray);
                if ($count > 0 && $destFullStreetArray[0] !== false) {
                    $destStreet = $destFullStreetArray[0];
                }
                if ($count > 1 && $destFullStreetArray[1] !== false) {
                    $destSuburb = $destFullStreetArray[1];
                }
            }
            $packageValue = $request->getPackageValue();
            $packageWeight = $request->getPackageWeight() * 1000;
            $post_data = [
                "customerDetails" => [
                    "shipperDetails" => [
                        "postalCode" => $fromPostalCode ?? '',
                        "cityName" => $fromCity ?? '',
                        "countryCode" => $fromCountry ?? '',
                        "provinceCode" => $fromCountry ?? '',
                        "addressLine1" => $fromStreet ?? '',
                        "countyName" => $fromRegion ?? $fromCity ?? '',
                    ],
                    "receiverDetails" => [
                        "postalCode" => $destPostcode ?? '',
                        "cityName" => $destCity ?? '',
                        "countryCode" => $destCountryId ?? $destCountryId ?? '',
                        "provinceCode" => $destCountryId ?? $destCountryId ?? '',
                        "addressLine1" => $destStreet ?? $destFullStreet ?? '',
                        "countyName" => $destSuburb ?? $destCity ?? '',
                    ]
                ],
                "accounts" => [
                    [
                        "typeCode" => "shipper",
                        "number" => $accountNumber
                    ]
                ],
                "productCode" => "P", // DHL Global Express product code
                "localProductCode" => "P", // DHL Express Local Product code
                "payerCountryCode" => $fromCountry ?? '',
                "plannedShippingDateAndTime" => "2022-03-22T08:00:00",
                "unitOfMeasurement" => "metric",
                "isCustomsDeclarable" => true,
                "monetaryAmount" => [
                    [
                        "typeCode" => "declaredValue",
                        "value" => 25,
                        "currency" => "UGX"
                    ]
                ],
                "requestAllValueAddedServices" => false,
                "returnStandardProductsOnly" => false,
                "nextBusinessDay" => false,
                "productTypeCode" => "all",
                "packages" => $this->getPackageCollection($request->getAllItems(), $request),
            ];

            $url = $this->helper->getConfigData('api/server');
            $url = $url . '/rates';

            $authToken = base64_encode(trim($this->helper->getConfigData('api/user')) . ':' . trim($this->helper->getConfigData('api/key')));
            $options = [CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . $authToken,
            ]];
            $this->_curl->setOptions($options);
            $this->_curl->post($url, json_encode($post_data));
            $response = $this->_curl->getBody();
            $json_obj = json_decode($response);
            $this->debugLogger->info('CARRIER ### response', [$json_obj]);
            $products = $json_obj->products ?? [];
            $rates_count = count($products);
            if ($rates_count > 0) {
                foreach ($products as $product) {
                    if (is_object($product)) {
                        $rate = $product->detailedPriceBreakdown[array_search($currencyType, array_column($product->detailedPriceBreakdown, 'currencyType'))] ?? null;
                        if (is_object($rate)) {
                            foreach ($rate->{'breakdown'} ?? [] as $key => $breakdown) {
                                // Add shipping option with shipping price
                                $method = $this->_rateMethodFactory->create();
                                $method->setCarrier($this->getCarrierCode());
                                $method->setCarrierTitle($this->getConfigData('title'));
                                $method->setMethod($breakdown->{'serviceCode'} ?? $breakdown->{'name'});
                                $method->setMethodTitle(ucfirst(strtolower($breakdown->{'name'})));
                                $method->setPrice(floatval($breakdown->{'price'} ?? 0.00));
                                $method->setCost(0);
                                $result->append($method);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->debugLogger->info("DHL Express Rates Exception");
            $this->debugLogger->info($e->getMessage());
        }

        return $result;
    }

    public function getPackageCollection($items, $request = null)
    {
        $packages = [];

        foreach ($items as $item) {
            /** @var \Magento\Quote\Model\Quote\Item $item **/
            $product = $item->getProduct();
            array_push($packages, [
                "typeCode" => "3BX",
                "weight" => floatval($product->getWeight() ?? (isset($request) ? $request->getPackageWeight() : 5)),
                "dimensions" => [
                    "length" => floatval($product->getData('length') ?? 10),
                    "width" => floatval($product->getData('width') ?? 5),
                    "height" => floatval($product->getData('height') ?? 10),
                ]
            ]);
        }
        return $packages;
    }

    public function collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        try {
            if (!$this->getConfigFlag('active')) {
                return false;
            }

            $result =  $this->_rateFactory->create();


            $expressWeightThreshold = $this->getConfigData('shipping_methods/express/express_weight_threshold') ?? 0;

            $eligibleForExpressDelivery = true;
            foreach ($request->getAllItems() as $_item) {
                if ($_item->getWeight() > $expressWeightThreshold) {
                    $eligibleForExpressDelivery = false;
                }
            }

            if ($eligibleForExpressDelivery) {
                $result = $this->_getExpressShippingRate($request);
            }

            $result->append($this->_getStandardShippingRate());

            if ($request->getFreeShipping()) {
                /**
                 *  If the request has the free shipping flag,
                 *  append a free shipping rate to the result.
                 */
                $freeShippingRate = $this->_getFreeShippingRate();
                $result->append($freeShippingRate);
            }

            return $result;
        } catch (\Throwable $th) {
            $this->debugLogger->info("DHL Express Rates Exception");
            $this->debugLogger->info($th->getMessage(), [$th->getTrace()]);
        }
    }


    protected function _doShipmentRequest(\Magento\Framework\DataObject $request)
    {
        unset($request);
        return null;
    }

    public function getAllowedMethods()
    {
        return $this->getMethods();
    }

    public function getTracking($tracking)
    {
        $result = $this->_trackFactory->create();
        $title = $this->getConfigData('title');
        $trackingUrl = $this->getTrackingUrl($tracking);

        /** @var \Magento\Shipping\Model\Tracking\Result\Status $trackStatus */
        $trackStatus = $this->_trackStatusFactory->create();
        $trackStatus->setCarrier($this->_code);
        $trackStatus->setCarrierTitle(__($title));
        $trackStatus->setTracking($tracking);
        $trackStatus->setUrl($trackingUrl);
        $trackStatus->addData([]);
        $result->append($trackStatus);

        return $result;
    }


    public function getTrackingUrl($trackerCode)
    {
        $piece = $this->pieceFactory->create();
        /** @var Piece $piece */
        $this->pieceResource->load($piece, $trackerCode, 'tracker_code');
        if (!$piece) {
            return false;
        }

        $postalCode = $piece->getPostalCode();
        $search = ['{{trackerCode}}', '{{postalCode}}'];
        $replace = [$trackerCode, $postalCode];
        return str_replace($search, $replace, $this->trackingUrl);
    }

    /**
     * @param null|string $key
     * @return array|string|null
     */
    protected function getMethods($key = null)
    {
        $methods = [
            'standard' => 'Standard',
            'express' => 'Express',
            'free_shipping' => 'Free Shipping',
        ];

        if (is_string($key)) {
            if (array_key_exists($key, $methods)) {
                return $methods[$key];
            }
            return null;
        }
        return $methods;
    }

    public function isZipCodeRequired($countryId = null)
    {
        unset($countryId);
        return false;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $items
     * @return array|bool
     */
    protected function createBlacklist($items)
    {
        $blacklist = [];

        foreach ($items as $item) {
            /** @var \Magento\Quote\Model\Quote\Item $item **/
            $product = $item->getProduct();
            if ($product[self::BLACKLIST_ALL]) {
                return true;
            }

            if ($product[self::BLACKLIST_SERVICEPOINT]) {
                $blacklist[] = 'PS';
            }
            foreach (explode(',', $product[self::BLACKLIST_GENERAL]) as $option) {
                $blacklist[] = $option;
            }
        }
        $this->debugLogger->debug('Blacklist: ' . implode(',', $blacklist));
        return array_unique($blacklist);
    }
}
