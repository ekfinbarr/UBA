<?php

namespace UBA\DHLExpress\Controller\Adminhtml\Log;

use UBA\DHLExpress\Model\Service\Notification as NotificationService;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;

class Reset extends \Magento\Backend\App\Action
{
    protected $dir;
    protected $notificationService;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        NotificationService $notificationService
    ) {
        $this->dir = $dir;
        $this->notificationService = $notificationService;
        parent::__construct($context);
    }

    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $logFile = $this->dir->getRoot() . \UBA\DHLExpress\Logger\DebugLogger::LOG_LOCATION;

            if (file_exists($logFile)) {
                file_put_contents($logFile, '');
                $message = __('Log file has been reset');
            } else {
                $message = __('Log file did not exist');
            }

            $response->setData([
                'status'  => 'success',
                'message' => $message
            ]);
        } catch (\Exception $e) {
            $response->setData([
                'status'  => 'failed',
                'message' => __('The following error occurred: %1', $e->getMessage())
            ]);
        }

        return $response;
    }
}
