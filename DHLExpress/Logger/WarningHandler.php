<?php

namespace UBA\DHLExpress\Logger;

class WarningHandler extends \Magento\Framework\Logger\Handler\Base
{
    protected $loggerType = DebugLogger::WARNING;
    protected $fileName = DebugLogger::LOG_LOCATION;
}
