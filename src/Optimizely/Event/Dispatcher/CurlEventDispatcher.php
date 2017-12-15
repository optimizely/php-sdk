<?php
/**
 * Copyright 2016, Optimizely
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
        $cmd.= " -d '".json_encode($event->getParams())."'";
        $cmd.= " '".$event->getUrl()."' > /dev/null 2>&1 &";
        exec($cmd, $output, $exit_code);

        if ($exit_code !== 0) {
            throw new Exception('Curl command failed.');
        }
    }
}
