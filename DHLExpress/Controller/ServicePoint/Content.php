<?php

namespace UBA\DHLExpress\Controller\ServicePoint;

use UBA\DHLExpress\Controller\AbstractResponse;

class Content extends AbstractResponse
{

    public function execute()
    {
        return $this->resultFactory
            ->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setData([
                'status'  => 'success',
                'data'    => [
                    'view' => $this->getTemplate('servicepoint.modal', [
                    ])
                ],
                'message' => null,
            ]);
    }
}
