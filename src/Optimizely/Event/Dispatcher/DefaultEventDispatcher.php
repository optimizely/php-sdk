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

use GuzzleHttp\Client as HttpClient;
use Optimizely\Event\LogEvent;

/**
 * Class DefaultEventDispatcher
 *
 * @package Optimizely\Event\Dispatcher
 */
class DefaultEventDispatcher implements EventDispatcherInterface
{
    /**
     * @const int Time in seconds to wait before timing out.
     */
    const TIMEOUT = 10;

    /**
     * @var \GuzzleHttp\Client Guzzle HTTP client to send requests.
     */
    private $httpClient;

    public function __construct(HttpClient $httpClient = null)
    {
        $this->httpClient = $httpClient ?: new HttpClient();
    }

    public function dispatchEvent(LogEvent $event)
    {
        $options = [
            'headers' => $event->getHeaders(),
            'json' => $event->getParams(),
            'timeout' => DefaultEventDispatcher::TIMEOUT,
            'connect_timeout' => DefaultEventDispatcher::TIMEOUT
        ];

        $this->httpClient->request($event->getHttpVerb(), $event->getUrl(), $options);
    }
}
