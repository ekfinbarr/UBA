<?php

namespace UBA\DHLExpress\Model\Service;

use UBA\DHLExpress\Helper\Data;
use UBA\DHLExpress\Logger\DebugLogger;
use UBA\DHLExpress\Model\Api\Connector;
use UBA\DHLExpress\Model\Cache\Api as ApiCache;
use UBA\DHLExpress\Model\Data\Capability\OptionFactory;
use UBA\DHLExpress\Model\Data\Capability\ProductFactory;
use UBA\DHLExpress\Model\Data\Api\Request\CapabilityCheckFactory;
use UBA\DHLExpress\Model\Data\Api\Response\CapabilityFactory;

class Capability
{
    protected $helper;
    protected $apiCache;
    protected $connector;
    protected $optionFactory;
    protected $productFactory;
    protected $capabilityCheckFactory;
    protected $capabilityFactory;
    protected $debugLogger;

    public function __construct(
        Data $helper,
        ApiCache $apiCache,
        Connector $connector,
        OptionFactory $optionFactory,
        ProductFactory $productFactory,
        CapabilityCheckFactory $capabilityCheckFactory,
        CapabilityFactory $capabilityFactory,
        DebugLogger $debugLogger
    ) {
        $this->helper = $helper;
        $this->apiCache = $apiCache;
        $this->connector = $connector;
        $this->optionFactory = $optionFactory;
        $this->productFactory = $productFactory;
        $this->capabilityCheckFactory = $capabilityCheckFactory;
        $this->capabilityFactory = $capabilityFactory;
        $this->debugLogger = $debugLogger;
    }

    public function getOptions($storeId, $toCountry = '', $toPostalCode = '', $toBusiness = false, $requestOptions = [])
    {
        $capabilityCheck = $this->createCapabilityCheck($storeId, $toCountry, $toPostalCode, $toBusiness, $requestOptions);
        $capabilities = $this->sendRequest($storeId, $capabilityCheck);

        $options = [];
        foreach ($capabilities as $capability) {
            if (!isset($capability->parcelType->key)) {
                continue;
            }

            if (empty($capability->options)) {
                continue;
            }

            foreach ($capability->options as $responseOption) {
                if (!$responseOption->key) {
                    continue;
                }

                if (!isset($options[$responseOption->key])) {
                    /** @var \UBA\DHLExpress\Model\Data\Capability\Option $option */
                    $option = $this->optionFactory->create();
                    $option->key = $responseOption->key;
                    $option->type = [];
                    $option->exclusions = [];
                    // Set exclusions only once
                    if (isset($responseOption->exclusions) && is_array($responseOption->exclusions)) {
                        foreach ($responseOption->exclusions as $exclusion) {
                            $option->exclusions[] = $exclusion->key;
                        }
                    }
                    $options[$responseOption->key] = $option;
                } else {
                    /** @var \UBA\DHLExpress\Model\Data\Capability\Option $option */
                    $option = $options[$responseOption->key];
                }

                // Add size to the stack of sizes, per service option
                $option->type[] = $capability->parcelType->key;
                $options[$responseOption->key] = $option;
            }
        }

        // Change to a full array
        $options = array_map(function ($value) {
            return $value->toArray();
        }, $options);

        return $options;
    }

    public function getSizes($storeId, $toCountry = '', $toPostalCode = '', $toBusiness = false, $requestOptions = [])
    {
        $capabilityCheck = $this->createCapabilityCheck($storeId, $toCountry, $toPostalCode, $toBusiness, $requestOptions);
        $this->debugLogger->info('Capability Check: ', [$capabilityCheck]);
        $capabilities = $this->sendRequest($storeId, $capabilityCheck);

        $products = [];
        $this->debugLogger->info('Capabilities: ',[$capabilities]);
        foreach ($capabilities as $capability) {
            if (!isset($capability->parcelType->key)) {
                continue;
            }

            if (!isset($capability->product->key)) {
                continue;
            }

            // Skip if already parsed
            if (isset($products[$capability->parcelType->key])) {
                continue;
            }

            /** @var \UBA\DHLExpress\Model\Data\Capability\Product $product */
            $product = $this->productFactory->create(['automap' => $capability->parcelType->toArray()]);
            $product->productKey = $capability->product->key;

            $products[$capability->parcelType->key] = $product->toArray();
        }

        array_multisort(array_column($products, 'maxWeightKg'), SORT_ASC, $products);

        return $products;
    }

    /**
     * @param int $storeId
     * @param $toCountry
     * @param $toPostalCode
     * @param $toBusiness
     * @param $requestOptions
     * @return \UBA\DHLExpress\Model\Data\Api\Request\CapabilityCheck
     */
    protected function createCapabilityCheck($storeId, $toCountry, $toPostalCode, $toBusiness, $requestOptions)
    {
        $fromCountry = $this->helper->getConfigData('shipper/country_code', $storeId);
        $fromPostalCode = $this->helper->getConfigData('shipper/postal_code', $storeId);
        $fromCity = $this->helper->getConfigData('shipper/city', $storeId);
        $accountNumber = $this->helper->getConfigData('api/account_id', $storeId);
        $exportAccountNumber = $this->helper->getConfigData('api/export_account', $storeId);
        $importAccountNumber = $this->helper->getConfigData('api/import_account', $storeId);
        $preferredCapability = $this->helper->getConfigData('capabilities/capability', $storeId);

        /** @var \UBA\DHLExpress\Model\Data\Api\Request\CapabilityCheck $capabilityCheck */
        // $capabilityCheck->fromCountry = trim($fromCountry);
        // $capabilityCheck->fromPostalCode = strtoupper($fromPostalCode);
        // $capabilityCheck->toCountry = trim($toCountry) ?: trim($fromCountry);
        // $capabilityCheck->accountNumber = $accountNumber;

        $capabilityCheck = $this->capabilityCheckFactory->create();
        $capabilityCheck->toBusiness = $toBusiness ? 'true' : 'false';
        $capabilityCheck->strictValidation = 'true';
        $capabilityCheck->countryCode = trim($fromCountry);

        if ($toPostalCode !== '') {
            // $capabilityCheck->toPostalCode = strtoupper($toPostalCode);
            $capabilityCheck->postalCode = strtoupper($fromPostalCode);
        }
        if ($fromCity !== '') {
            $capabilityCheck->cityName = trim($fromCity);
        }

        if (trim($fromCountry) !== trim($toCountry)) {
            $capabilityCheck->accountNumber = trim($exportAccountNumber);
        } else {
            $capabilityCheck->accountNumber = trim($importAccountNumber);
        }

        if (is_array($requestOptions) && count($requestOptions)) {
            $capabilityCheck->option = implode(',', $requestOptions);
        }

        if ($preferredCapability !== '') {
            $type = trim($preferredCapability);
            ($type === 'auto') ? $type = 'delivery' : null;
            $capabilityCheck->type = $type;
        } else {
            $capabilityCheck->type = 'delivery';
        }

        return $capabilityCheck;
    }

    /**
     * @param int $storeId
     * @param \UBA\DHLExpress\Model\Data\Api\Request\CapabilityCheck $capabilityCheck
     * @return \UBA\DHLExpress\Model\Data\Api\Response\Capability[]
     */
    protected function sendRequest($storeId, $capabilityCheck)
    {
        $cacheKey = $this->apiCache->createKey($storeId, 'capabilities', $capabilityCheck->toArray(true));
        $json = $this->apiCache->load($cacheKey);

        if ($json === false) {
            $response = $this->connector->get('address-validate', $capabilityCheck->toArray(true));
            if (!empty($response)) {
                $this->apiCache->save(json_encode($response), $cacheKey, [], 3600);
            }
        } else {
            $response = json_decode($json, true);
        }

        $capabilities = [];
        if ($response && is_array($response)) {
            foreach ($response as $entry) {
                $capabilities[] = $this->capabilityFactory->create(['automap' => $entry]);
            }
        }
        return $capabilities;
    }
}
