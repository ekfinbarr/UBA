<?php
namespace UBA\DHLExpress\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(\Magento\Framework\App\Helper\Context $context)
    {
        parent::__construct($context);
    }

    /**
     * @param $configPath
     * @param null $storeId
     * @param null $scope
     * @return mixed
     */
    public function getConfigData($configPath, $storeId = null, $scope = null)
    {
        return $this->scopeConfig->getValue(
            'carriers/dhlexpress/' . $configPath,
            $scope ?: ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
