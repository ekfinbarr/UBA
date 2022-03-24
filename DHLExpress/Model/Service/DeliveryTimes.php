<?php

namespace UBA\DHLExpress\Model\Service;

use UBA\DHLExpress\Helper\Data;
use UBA\DHLExpress\Model\Api\Connector;
use UBA\DHLExpress\Model\Data\DeliveryTimeFactory;
use UBA\DHLExpress\Model\Data\Api\Response\TimeWindowFactory;
use UBA\DHLExpress\Model\Data\TimeSelectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Config\Model\Config\Source\Locale\WeekdaysFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class DeliveryTimes
{
    const SHIPPING_PRIORITY_BACKLOG = 'shipping_priority_backlog';
    const SHIPPING_PRIORITY_SOON = 'shipping_priority_soon';
    const SHIPPING_PRIORITY_TODAY = 'shipping_priority_today';
    const SHIPPING_PRIORITY_ASAP = 'shipping_priority_asap';

    protected $helper;
    protected $connector;
    protected $deliveryTimeFactory;
    protected $timeWindowFactory;
    protected $stockRegistry;
    protected $checkoutSession;
    /** @var \Magento\Config\Model\Config\Source\Locale\Weekdays */
    protected $weekdays;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    public function __construct(
        Data $helper,
        Connector $connector,
        DeliveryTimeFactory $deliveryTimeFactory,
        TimeWindowFactory $timeWindowFactory,
        TimeSelectionFactory $timeSelectionFactory,
        StockRegistryInterface $stockRegistry,
        CheckoutSession $checkoutSession,
        WeekdaysFactory $weekdaysFactory,
        TimezoneInterface $timezone
    ) {
        $this->helper = $helper;
        $this->connector = $connector;
        $this->deliveryTimeFactory = $deliveryTimeFactory;
        $this->timeWindowFactory = $timeWindowFactory;
        $this->timeSelectionFactory = $timeSelectionFactory;
        $this->stockRegistry = $stockRegistry;
        $this->checkoutSession = $checkoutSession;
        $this->weekdays = $weekdaysFactory->create();
        $this->timezone = $timezone;
    }

    public function isEnabled()
    {
        $enabledSetting = $this->helper->getConfigData('delivery_times/enabled');
        return boolval($enabledSetting === '1');
    }

    public function displayFrontend()
    {
        $toBusiness = boolval($this->helper->getConfigData('label/default_to_business'));
        if ($toBusiness) {
            return false;
        }

        return true;
    }

    public function notInStock()
    {
        $stockSetting = $this->helper->getConfigData('delivery_times/in_stock_only');
        if (boolval($stockSetting !== '1')) {
            return false;
        }

        $items = $this->checkoutSession->getQuote()->getAllVisibleItems();
        foreach ($items as $item) {
            if (!$this->stockRegistry->getStockItemBySku($item->getSku())->getIsInStock()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $postalCode
     * @param $countryCode
     * @return \UBA\DHLExpress\Model\Data\DeliveryTime[]
     */
    public function getTimeFrames($postalCode, $countryCode)
    {
        if (!$postalCode || !$countryCode) {
            return [];
        }

        $trimmedPostalCode = preg_replace('/\s+/', '', $postalCode);

        $timeWindowsResponse = $this->connector->get('time-windows', [
            'countryCode' => $countryCode,
            'postalCode' => strtoupper($trimmedPostalCode),
        ]);

        if (!$timeWindowsResponse || !is_array($timeWindowsResponse) || empty($timeWindowsResponse)) {
            return [];
        }

        $deliveryTimes = [];
        foreach ($timeWindowsResponse as $timeWindowData) {
            $timeWindow = $this->timeWindowFactory->create(['automap' => $timeWindowData]);
            $deliveryTime = $this->parseTimeWindow($timeWindow->deliveryDate, $timeWindow->startTime, $timeWindow->endTime, $timeWindow);
            $deliveryTimes[] = $deliveryTime;
        }

        return $deliveryTimes;
    }

    /**
     * @param $sourceDeliveryDate
     * @param $sourceStartTime
     * @param $sourceEndTime
     * @param \UBA\DHLExpress\Model\Data\Api\Response\TimeWindow $timeWindow
     * @param string $compare
     * @return \UBA\DHLExpress\Model\Data\DeliveryTime
     */
    public function parseTimeWindow($sourceDeliveryDate, $sourceStartTime, $sourceEndTime, $timeWindow = null)
    {
        $timezoneString = $this->timezone->getConfigTimezone();
        $timezone = new \DateTimeZone($timezoneString);

        $deliveryDate = date_create_from_format('d-m-Y Hi', $sourceDeliveryDate . ' ' . $sourceStartTime, $timezone);
        $deliveryDateEnd = date_create_from_format('d-m-Y Hi', $sourceDeliveryDate . ' ' . $sourceEndTime, $timezone);

        if (!$deliveryDate) {
            return null;
        }

        $dayName = __($deliveryDate->format('D')) . '.';
        $dayOfTheMonth = $deliveryDate->format('j');
        $monthName = __($deliveryDate->format('M')) . '.';
        $date = implode(' ', [$dayName, $dayOfTheMonth, $monthName]);
        $weekDay = $deliveryDate->format('w');
        $day = $deliveryDate->format('w');
        $month = $deliveryDate->format('n');
        $year = $deliveryDate->format('Y');
        $startTime = $deliveryDate->format('H:i');
        $endTime = $deliveryDateEnd->format('H:i');

        $identifier = $this->getIdentifier($sourceDeliveryDate, $sourceStartTime, $sourceEndTime);

        return $this->deliveryTimeFactory->create(['automap' => [
            'source' => $timeWindow,

            'date'    => $date,
            'weekDay' => $weekDay,
            'day'     => $day,
            'month'   => $month,
            'year'    => $year,

            'startTime' => $startTime,
            'endTime'   => $endTime,

            'displayLabel' => sprintf('%1$s (%2$s) - (%3$s)', $date, $startTime, $endTime),
            'identifier' => $identifier
        ]]);
    }

    /**
     * @param \UBA\DHLExpress\Model\Data\DeliveryTime[]
     * @param bool $dayTime
     * @return \UBA\DHLExpress\Model\Data\DeliveryTime[]
     */
    public function filterTimeFrames($deliveryTimes, $dayTime = true)
    {
        $filteredTimes = [];

        $cutoffGeneral = $this->getCutoffTimestamp();
        $currentDayTimestamp = $this->timezone->date();
        $currentDayTimestamp->setTime(23, 59, 59);
        $todayMidnightTimestamp = $currentDayTimestamp->getTimestamp();

        $displayDays = intval($this->helper->getConfigData('delivery_times/display_days'));
        $displayDays += 1; // When setting '1 display day' for example, to make tomorrow available, you actually add 2 days. One for today, one for tomorrow. Thus you always add this one additional day to the check.
        $maxShowDate = $this->timezone->date();
        $maxTimestamp = $maxShowDate->modify('+' . $displayDays . ' day')->getTimestamp();

        $shippingDays = $this->getShippingDays();

        if (empty($shippingDays)) {
            return [];
        }

        $timezoneString = $this->timezone->getConfigTimezone();
        $timezone = new \DateTimeZone($timezoneString);

        foreach ($deliveryTimes as $deliveryTime) {
            $datetime = date_create_from_format('d-m-Y Hi', $deliveryTime->source->deliveryDate . ' ' . $deliveryTime->source->startTime, $timezone);
            $timestamp = $datetime->getTimestamp();

            if ($timestamp < $todayMidnightTimestamp) {
                continue;
            }

            if ($timestamp > $maxTimestamp) {
                continue;
            }

            if (!$cutoffGeneral) {
                continue;
            }

            if ($this->validateWithShippingDays($cutoffGeneral, $timestamp, $shippingDays)) {
                // This is an intentional ambiguous check, due to the lack of strict regulations on the type of input from the Time Window API so far
                // Check if end time is AFTER 18:00 (int check), or is exactly 00:00
                if (intval($deliveryTime->source->startTime) > 1400
                    && (intval($deliveryTime->source->endTime) > 1800 || $deliveryTime->source->endTime === '0000')) {
                    // Evening
                    if ($dayTime !== true) {
                        $filteredTimes[] = $deliveryTime;
                    }
                } else {
                    // Day
                    if ($dayTime !== false) {
                        $filteredTimes[] = $deliveryTime;
                    }
                }
            }
        }

        return $filteredTimes;
    }

    public function showSameday()
    {
        $isEnabled = boolval($this->helper->getConfigData('shipping_methods/sameday/enabled'));

        if (!$isEnabled) {
            return false;
        }

        /** @var \DateTime $currentDateTime */
        $currentDateTime = $this->timezone->date();
        $shippingDays    = explode(',', $this->helper->getConfigData('delivery_times/shipping_days'));

        if (!in_array($currentDateTime->format('w'), $shippingDays)) {
            return false;
        }

        $cutoffHour = 18; // Default to 18:00 if not using cutoff setting
        $cutoffSetting = $this->helper->getConfigData('shipping_methods/sameday/cutoff');

        if ($cutoffSetting) {
            $cutoffHour = intval($cutoffSetting);
        }

        $currentHour = intval($currentDateTime->format('G'));

        return $currentHour < $cutoffHour;
    }

    public function showPriority()
    {
        $enabled = $this->isEnabled();
        $sameDayEnabled = $this->helper->getConfigData('shipping_methods/sameday/enabled');
        if (!$enabled && !$sameDayEnabled) {
            return false;
        }

        return true;
    }

    public function saveSamedaySelection($order)
    {
        $currentDateTime = $this->timezone->date();
        $date = $currentDateTime->format('d-m-Y');
        $startTime = '1800';
        $endTime = '2100';

        $this->saveTimeSelection($order, $date, $startTime, $endTime);
    }

    public function saveTimeSelection($order, $date, $startTime, $endTime)
    {
        if (empty($order) || !$order instanceof \Magento\Sales\Api\Data\OrderInterface) {
            return;
        }

        if (empty($date) || empty($startTime) || empty($endTime)) {
            return;
        }

        /** @var \UBA\DHLExpress\Model\Data\TimeSelection $timeSelection */
        $timeSelection = $this->timeSelectionFactory->create();
        $timeSelection->date = $date;
        $timeSelection->startTime = $startTime;
        $timeSelection->endTime = $endTime;

        $timezoneString = $this->timezone->getConfigTimezone();
        $timezone = new \DateTimeZone($timezoneString);

        $datetime = date_create_from_format('d-m-Y Hi', $date . ' ' . $startTime, $timezone);
        $timeSelection->timestamp = $datetime->getTimestamp();

        $order->setData('uba_dhlexpress_deliverytimes_selection', $timeSelection->toJSON());
        $order->setData('uba_dhlexpress_deliverytimes_priority', 9999999999 - intval($timeSelection->timestamp)); // Compatible up to year 2286

        $order->setShippingDescription($order->getShippingDescription());
    }

    /**
     * @param $order
     * @return \UBA\DHLExpress\Model\Data\TimeSelection
     */
    public function getTimeSelection($order)
    {
        if (empty($order) || !$order instanceof \Magento\Sales\Api\Data\OrderInterface) {
            return null;
        }

        $timeSelectionJson = $order->getData('uba_dhlexpress_deliverytimes_selection');
        if (empty($timeSelectionJson)) {
            return null;
        }

        $timeSelectionData = json_decode($timeSelectionJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $this->timeSelectionFactory->create(['automap' => $timeSelectionData]);
    }

    public function getShippingAdviceClass($selectedTimestamp)
    {
        $shippingPriority = $this->getShippingPriority($selectedTimestamp);

        switch ($shippingPriority) {
            case self::SHIPPING_PRIORITY_TODAY:
                return 'uba-dhlexpress-advice-today';
                break;

            case self::SHIPPING_PRIORITY_SOON:
                return 'uba-dhlexpress-advice-soon';
                break;

            case self::SHIPPING_PRIORITY_ASAP:
                return 'uba-dhlexpress-advice-asap';
                break;

            default:
                return 'uba-dhlexpress-advice-backlog';
        }
    }

    public function getTimeLeft($timestamp)
    {
        $currentDateTime = $this->timezone->date();
        $currentTimestamp = $currentDateTime->getTimestamp();
        if ($currentTimestamp > $timestamp) {
            return null;
        }
        return $this->humanTimeDiff($currentTimestamp, $timestamp);
    }

    public function getShippingAdvice($selectedTimestamp)
    {
        $shippingPriority = $this->getShippingPriority($selectedTimestamp);

        switch ($shippingPriority) {
            case self::SHIPPING_PRIORITY_ASAP:
                return __("Send\nASAP");
                break;

            case self::SHIPPING_PRIORITY_SOON:
                return __("Send\ntomorrow");
                break;

            case self::SHIPPING_PRIORITY_BACKLOG:
                $dayInSeconds = 24 * 60 * 60;

                // Get Timestamp op todat (without time)
                $currentTime = $this->timezone->date();
                $currentTime->setTime(0, 0, 0);
                $currentDayTimestamp = $currentTime->getTimestamp();

                // Get Tomorrow Timestamp
                $tomorrowDayTimestamp = $currentDayTimestamp + $dayInSeconds;

                // Get Timestamp of selected day (without time)
                $selectedDayDateOnly = $this->timezone->date();
                $selectedDayDateOnly->setTimestamp($selectedTimestamp);
                $selectedDayDateOnly->setTime(0, 0, 0);
                $selectedDayTimestamp = $selectedDayDateOnly->getTimestamp();

                $daysDifferenceTimestamp = $selectedDayTimestamp - $tomorrowDayTimestamp;
                $daysBetween = floor($daysDifferenceTimestamp / $dayInSeconds);
                return sprintf(__("Send in\n%s days"), $daysBetween);
                break;

            default:
                return __("Send\ntoday");
        }
    }

    protected function getShippingPriority($selectedTimestamp)
    {
        $currentDateTime = $this->timezone->date();
        $currentTimestamp = $currentDateTime->getTimestamp();
        if ($currentTimestamp > $selectedTimestamp) {
            return self::SHIPPING_PRIORITY_ASAP;
        }

        $dayInSeconds = 24 * 60 * 60;

        $currentDateTime = $this->timezone->date();
        $currentDayTimestamp = $currentDateTime->getTimestamp();
        $tomorrowDayTimestamp = $currentDayTimestamp + $dayInSeconds;

        $selectedTimestampDateTime = $this->timezone->date();
        $selectedTimestampDateTime->setTimestamp($selectedTimestamp);
        $selectedTimestampDateTime->setTime(0, 0, 0);
        $selectedDayTimestamp = $selectedTimestampDateTime->getTimestamp();

        if ($currentDayTimestamp >= $selectedDayTimestamp) {
            return self::SHIPPING_PRIORITY_ASAP;
        }

        if ($tomorrowDayTimestamp < $selectedDayTimestamp) {
            $daysDifferenceTimestamp = $selectedDayTimestamp - $tomorrowDayTimestamp;
            $dayInSeconds = 24 * 60 * 60;
            $daysBetween = floor($daysDifferenceTimestamp / $dayInSeconds);

            if ($daysBetween == 1) {
                return self::SHIPPING_PRIORITY_SOON;
            }

            return self::SHIPPING_PRIORITY_BACKLOG;
        }

        return self::SHIPPING_PRIORITY_TODAY;
    }

    protected function getCutoffTimestamp()
    {
        $currentDateTime = $this->timezone->date();
        $dayInSeconds = 24 * 60 * 60;
        $cutoffSetting = $this->helper->getConfigData('delivery_times/cutoff');
        $cutoffHour = intval($cutoffSetting);
        $currentHour = intval($currentDateTime->format('G'));

        $cutoff = boolval($currentHour >= $cutoffHour);

        // set the time of the DateTime object to 0:00:00, after which we wil remove 1 second to make it yesterday
        $currentDateTime->setTime(0, 0, 0);
        $currentDayTimestamp = $currentDateTime->getTimestamp() - 1;

        $transitDaysSetting = $this->helper->getConfigData('delivery_times/transit_days');
        $days = intval($transitDaysSetting);
        $days += $cutoff ? 1 : 0;
        $addDays = $dayInSeconds * $days;

        return $currentDayTimestamp + $addDays;
    }

    protected function getShippingDays()
    {
        $shippingDaysSetting = $this->helper->getConfigData('delivery_times/shipping_days');
        $shippingDaysArray = array_map('trim', explode(',', $shippingDaysSetting));

        $weekdays = $this->weekdays->toOptionArray();

        $shippingDays = [];
        foreach ($weekdays as $weekday) {
            $number = intval($weekday['value']);
            $available = boolval(in_array($number, $shippingDaysArray));
            if ($number === 0) {
                // Convert Sunday to equal date:N value of Sunday
                $number = 7;
            }
            $shippingDays[$number] = $available;
        }

        return $shippingDays;
    }

    protected function validateWithShippingDays($minimumTimestamp, $timestamp, $shippingDays)
    {
        // First check if the day before the select date is a shipping day. It will be impossible to deliver on time if not delivered the day before.
        // TODO Note, currently using a hardcoded check for Sundays. Drop off timing does not work for Sundays.
        $dayInSeconds = 24 * 60 * 60;
        $dayBeforeTimeStamp = $timestamp - $dayInSeconds;

        $dayBeforeDateTime = $this->timezone->date();
        $dayBeforeDateTime->setTimestamp($dayBeforeTimeStamp);

        $dateBeforeCode = intval($dayBeforeDateTime->format('N'));
        if (($shippingDays[$dateBeforeCode] !== true && $dateBeforeCode != 7) || ($dateBeforeCode == 7 && $shippingDays[6] !== true)) {
            return false;
        }

        $currentDateTime = $this->timezone->date();
        $timestampTodayCheck = $currentDateTime->getTimestamp() - 1;
        $timestampDifference = $timestamp - $timestampTodayCheck;
        if ($timestampDifference < 0) {
            // Unknown validation, shipping day is lower than current timestamp
            return false;
        }

        $dayInSeconds = 24 * 60 * 60;
        $daysBetween = floor($timestampDifference / $dayInSeconds);

        if ($daysBetween > 30) {
            // In case invalid timestamps are given, prevent endless loops and fail the validation
            return false;
        }

        $additionalDays = 0;
        for ($dayCheck = 0; $dayCheck < $daysBetween; $dayCheck++) {
            $currentDateTime = $this->timezone->date();
            $theDay = intval($currentDateTime->modify('+' . $dayCheck . ' day')->format('N'));
            if ($shippingDays[$theDay] !== true) {
                $additionalDays++;
            }
        }

        // Add the additional days to the minimum timestamp
        $minimumPlusDays = $additionalDays * $dayInSeconds;
        $minimumTimestamp = $minimumPlusDays + $minimumTimestamp;

        if ($minimumTimestamp > $timestamp) {
            return false;
        }

        return true;
    }

    protected function getIdentifier($date, $start_time, $end_time)
    {
        return $date . '___' . $start_time . '___' . $end_time;
    }

    /**
     * Ported from WC
     *
     * @param $from
     * @param string $to
     * @return string
     */
    protected function humanTimeDiff($from, $to = '')
    {
        $minuteInSeconds = 60;
        $hourInSeconds = 60 * $minuteInSeconds;
        $dayInSeconds = 24 * $hourInSeconds;
        $weekInSeconds = 7 * $dayInSeconds;
        $monthInSeconds = 30 * $dayInSeconds;
        $yearInSeconds = 365 * $dayInSeconds;

        if (empty($to)) {
            $to = time();
        }

        $diff = (int)abs($to - $from);

        if ($diff < $hourInSeconds) {
            $mins = round($diff / $minuteInSeconds);
            if ($mins <= 1) {
                $mins = 1;
            }
            $since = sprintf(__('%s min(s)'), $mins);
        } elseif ($diff < $dayInSeconds && $diff >= $hourInSeconds) {
            $hours = round($diff / $hourInSeconds);
            if ($hours <= 1) {
                $hours = 1;
            }
            $since = sprintf(__('%s hour(s)'), $hours);
        } elseif ($diff < $weekInSeconds && $diff >= $dayInSeconds) {
            $days = round($diff / $dayInSeconds);
            if ($days <= 1) {
                $days = 1;
            }
            $since = sprintf(__('%s day(s)'), $days);
        } elseif ($diff < $monthInSeconds && $diff >= $weekInSeconds) {
            $weeks = round($diff / $weekInSeconds);
            if ($weeks <= 1) {
                $weeks = 1;
            }
            $since = sprintf(__('%s week(s)'), $weeks);
        } elseif ($diff < $yearInSeconds && $diff >= $monthInSeconds) {
            $months = round($diff / $monthInSeconds);
            if ($months <= 1) {
                $months = 1;
            }
            $since = sprintf(__('%s month(s)'), $months);
        } elseif ($diff >= $yearInSeconds) {
            $years = round($diff / $yearInSeconds);
            if ($years <= 1) {
                $years = 1;
            }
            $since = sprintf(__('%s year(s)'), $years);
        }

        return $since;
    }
}
