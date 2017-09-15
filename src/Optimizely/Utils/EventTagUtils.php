<?php
/**
 * Copyright 2017, Optimizely
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

class EventTagUtils
{
    /**
     * @const string Reserved word for event tag representing event revenue value.
     */
    const REVENUE_EVENT_METRIC_NAME = 'revenue';

    const NUMERIC_EVENT_METRIC_NAME = 'value';

    /**
     * Grab the revenue value from the event tags. "revenue" is a reserved keyword.
     * The revenue value must be an integer, not  a numeric string.   
     * @param $eventTags array Representing metadata associated with the event.
     * @return integer Revenue value as an integer number or null if revenue can't be retrieved from the event tags
     */
    public static function getRevenueValue($eventTags) {
        if (!$eventTags) {
            return null;
        }
        if (!is_array($eventTags)) {
            return null;
        }

        if (!isset($eventTags[self::REVENUE_EVENT_METRIC_NAME])) {
            return null;
        }

        $raw_value = $eventTags[self::REVENUE_EVENT_METRIC_NAME];
        if (!is_int($raw_value)) {
            return null;
        }

        return $raw_value;
    }

    /**
     * Grab the numeric event value from the event tags. "value" is a reserved keyword.
     * The value of 'value' can be a float or a numeric string
     * @param $eventTags array Representing metadata associated with the event.
     * @return float value of 'value' or null
     */
    public static function getNumericValue($eventTags, LoggerInterface $logger = null){

        $debugMsg = "";
        $isValid = true;

        if (!$eventTags) {
            $debugMsg = 'Event tags is undefined.';
            $isValid = false;
        }
        else if (!is_array($eventTags)) {
            $debugMsg = 'Event tags is not a dictionary.';
            $isValid = false;
        }

        else if (!isset($eventTags[self::NUMERIC_EVENT_METRIC_NAME])) {
            $debugMsg = 'The numeric metric key is not in event tags or is null.';
            $isValid = false;
        }

        else if (!is_numeric($eventTags[self::NUMERIC_EVENT_METRIC_NAME])) {
            $debugMsg = 'Numeric metric value is not in integer or float form, or is not a numeric string';
            $isValid = false;
        }

        else if(is_nan($eventTags[self::NUMERIC_EVENT_METRIC_NAME]) || is_infinite(floatval($eventTags[self::NUMERIC_EVENT_METRIC_NAME]))){
            $debugMsg = 'Provided numeric value is in an invalid format.';
            $isValid = false;
        }

        // Log all potential debug messages
        if($logger && $debugMsg){
            $logger->log(Logger::DEBUG,$debugMsg);
        }

        $rawValue = $isValid? $eventTags[self::NUMERIC_EVENT_METRIC_NAME]: null;

        // # Log the final numeric metric value
        if($logger){
            if($isValid){
                $logger->log(Logger::INFO,"The numeric metric value {$rawValue} will be sent to results.");
            } 
            else{
                $logger->log(Logger::WARNING,"The provided numeric metric value {$rawValue} is in an invalid format and will not be sent to results.");
            } 
        }

        return $isValid? floatval($rawValue): null;
    }
}
