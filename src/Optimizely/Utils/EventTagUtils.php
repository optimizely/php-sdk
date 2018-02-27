<?php
/**
 * Copyright 2017-2018, Optimizely
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Optimizely\Utils;

use Optimizely\Logger\LoggerInterface;
use Optimizely\Logger\NoOpLogger;
use Monolog\Logger;

class EventTagUtils
{
    /**
     * @const string Reserved word for event tag representing event revenue value.
     */
    const REVENUE_EVENT_METRIC_NAME = 'revenue';

    const NUMERIC_EVENT_METRIC_NAME = 'value';

    /**
     * Grab the revenue value from the event tags. "revenue" is a reserved keyword.
     * The value will be parsed to an integer if possible.
     * Example:
     *     4.0 or "4.0" will be parsed to int(4).
     *     4.1 will not be parsed and the method will return null.
     *
     * @param  $eventTags array Representing metadata associated with the event.
     * @return integer Revenue value as an integer number or null if revenue can't be retrieved from the event tags
     */
    public static function getRevenueValue($eventTags, $logger)
    {
        if (!$eventTags) {
            $logger->log(Logger::DEBUG, "Event tags is undefined.");
            return null;
        }
        if (!is_array($eventTags)) {
            $logger->log(Logger::DEBUG, "Event tags is not a dictionary.");
            return null;
        }

        if (!isset($eventTags[self::REVENUE_EVENT_METRIC_NAME])) {
            $logger->log(Logger::DEBUG, "The revenue key is not defined in the event tags or is null.");
            return null;
        }

        $rawValue = $eventTags[self::REVENUE_EVENT_METRIC_NAME];

        if (!is_numeric($rawValue)) {
            $logger->log(Logger::DEBUG, "Revenue value is not an integer or float, or is not a numeric string.");
            return null;
        }

        if ($rawValue != intval($rawValue)) {
            $logger->log(Logger::DEBUG, "Revenue value couldn't be parsed as an integer.");
            return null;
        }

        $rawValue = intval($rawValue);
        $logger->log(Logger::INFO, "The revenue value {$rawValue} will be sent to results.");
        return $rawValue;
    }

    /**
     * Grab the numeric event value from the event tags. "value" is a reserved keyword.
     * The value of 'value' can be a float or a numeric string
     *
     * @param  $eventTags array Representing metadata associated with the event.
     * @param $logger instance of LoggerInterface
     *
     * @return float value of 'value' or null
     */
    public static function getNumericValue($eventTags, $logger)
    {
        if (!$eventTags) {
            $logger->log(Logger::DEBUG, "Event tags is undefined.");
            return null;
        }

        if (!is_array($eventTags)) {
            $logger->log(Logger::DEBUG, "Event tags is not a dictionary.");
            return null;
        }

        if (!isset($eventTags[self::NUMERIC_EVENT_METRIC_NAME])) {
            $logger->log(Logger::DEBUG, "The numeric metric key is not defined in the event tags or is null.");
            return null;
        }

        if (!is_numeric($eventTags[self::NUMERIC_EVENT_METRIC_NAME])) {
            $logger->log(Logger::DEBUG, "Numeric metric value is not an integer or float, or is not a numeric string.");
            return null;
        }

        if (is_nan($eventTags[self::NUMERIC_EVENT_METRIC_NAME]) || is_infinite(floatval($eventTags[self::NUMERIC_EVENT_METRIC_NAME]))) {
            $logger->log(Logger::DEBUG, "Provided numeric value is in an invalid format.");
            return null;
        }

        $rawValue = $eventTags[self::NUMERIC_EVENT_METRIC_NAME];
        // # Log the final numeric metric value
        $logger->log(Logger::INFO, "The numeric metric value {$rawValue} will be sent to results.");

        return floatval($rawValue);
    }
}
