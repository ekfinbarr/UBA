<?php

namespace UBA\DHLExpress\Logger;

class NoticeHandler extends \Magento\Framework\Logger\Handler\Base
{
    protected $loggerType = DebugLogger::NOTICE;
    protected $fileName = DebugLogger::LOG_LOCATION;
}
