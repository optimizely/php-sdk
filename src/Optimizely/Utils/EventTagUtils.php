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
     * Grab the revenue value from the event tags. "revenue" is a reserved keyword.
     *
     * @param $eventTags array representing metadata associated with the event.
     * @return number revenue value as an integer number or null if revenue can't be retrieved from the event tags
     */
    public static function getRevenueValue($eventTags) {
        if (!$eventTags) {
            return null;
        }
        if (!is_array($eventTags)) {
            return null;
        }

        if (!$eventTags['revenue']) {
            return null;
        }

        $raw_value = $eventTags['revenue'];
        if (!is_int($raw_value)) {
            return null;
        }

        return $raw_value;
    }
}
