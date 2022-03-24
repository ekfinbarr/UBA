<?php

namespace UBA\DHLExpress\Controller\DeliveryTimes;

use UBA\DHLExpress\Model\Service\DeliveryTimes as DeliveryTimesService;

class Times extends \UBA\DHLExpress\Controller\AbstractResponse
{
    protected $deliveryTimesService;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        DeliveryTimesService $deliveryTimesService
    ) {
        parent::__construct($context);
        $this->deliveryTimesService = $deliveryTimesService;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPost();
        $postcode = $data->postcode ?: null;
        $country = $data->country ?: null;

        $deliveryTimes = $this->deliveryTimesService->getTimeFrames($postcode, $country);
        $allTimes = $this->deliveryTimesService->filterTimeFrames($deliveryTimes, null);
        $dayTimes = $this->deliveryTimesService->filterTimeFrames($deliveryTimes);
        $nightTimes = $this->deliveryTimesService->filterTimeFrames($deliveryTimes, false);

        return $this->resultFactory
            ->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setData([
                'status'  => 'success',
                'data'    => [
                    'allTimes'   => $allTimes,
                    'dayTimes'   => $dayTimes,
                    'nightTimes' => $nightTimes,
                ],
                'message' => null
            ]);
    }
}
