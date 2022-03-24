<?php

namespace UBA\DHLExpress\Controller;

abstract class AbstractResponse extends \Magento\Framework\App\Action\Action
{
    /**
     * Create an instant phtml view
     *
     * @param $id
     * @param array $data
     *
     * @return mixed
     */
    protected function getTemplate($id, $data = [])
    {
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_LAYOUT)
            ->getLayout()
            ->createBlock('UBA\DHLExpress\Block\Ajax', $id)
            ->setData($data)
            ->setTemplate($id . '.phtml')
            ->setArea(\Magento\Framework\App\Area::AREA_FRONTEND)
            ->setIsSecureMode(true)
            ->toHtml();
    }
}
