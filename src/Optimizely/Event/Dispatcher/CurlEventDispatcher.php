<?php
/**
 * Copyright 2016, 2019, Optimizely
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

namespace Optimizely\Event\Dispatcher;

use Exception;
use Optimizely\Event\LogEvent;

/**
 * Class CurlEventDispatcher
 *
 * @package Optimizely\Event\Dispatcher
 */
class CurlEventDispatcher implements EventDispatcherInterface
{
    public function dispatchEvent(LogEvent $event)
    {
        $cmd = "curl";
        $cmd.= " -X ".$event->getHttpVerb();
        foreach ($event->getHeaders() as $type => $value) {
            $cmd.= " -H '".$type.": ".$value."'";
        }

        $eventParams = $this->sanitizeEventPayload($event->getParams());

        $cmd.= " -d '".json_encode($eventParams)."'";
        $cmd.= " '".$event->getUrl()."' > /dev/null 2>&1 &";
        exec($cmd, $output, $exit_code);

        if ($exit_code !== 0) {
            throw new Exception('Curl command failed.');
        }
    }

    /**
     * Escapes certain user provided values from event payload which includes
     * 1. user ID.
     * 2. attribute values of type string.
     * 3. event tag keys.
     * 4. event tag values of type string.
     *
     * @param  array  $params LogEvent params.
     *
     * @return array Escaped params.
     **/
    public function sanitizeEventPayload(array $params)
    {

        function escapeIfString($val)
        {
            if (is_string($val)) {
                return escapeshellarg($val);
            }

            return $val;
        }

        // user ID
        $params['visitors'][0]['visitor_id'] = escapeshellarg($params['visitors'][0]['visitor_id']);

        // string type attribute values
        $attributes = $params['visitors'][0]['attributes'];
        $escapedAttributes = [];

        foreach ($attributes as $attr) {
            $attr['value'] = escapeIfString($attr['value']);
             array_push($escapedAttributes, $attr);
        }

        $params['visitors'][0]['attributes'] = $escapedAttributes;

        // event tags if present
        if (isset($params['visitors'][0]['snapshots'][0]['events'][0]['tags'])) {
            $eventTags = $params['visitors'][0]['snapshots'][0]['events'][0]['tags'];
            $escapedEventTags = [];

            foreach ($eventTags as $key => $value) {
                $key = escapeIfString($key);
                $value = escapeIfString($value);

                $escapedEventTags[$key] = $value;
            }
        }

        $params['visitors'][0]['snapshots'][0]['events'][0]['tags'] = $escapedEventTags;

        return $params;
    }
}
