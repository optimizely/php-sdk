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
namespace Optimizely\Tests;

use GuzzleHttp\Client as HttpClient;
use Optimizely\Event\Dispatcher\DefaultEventDispatcher;
use Optimizely\Event\LogEvent;

class DefaultEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testDispatchEvent()
    {
        $logEvent = new LogEvent(
            'https://logx.optimizely.com',
            [
                'accountId' => '1234',
                'projectId' => '9876',
                'visitorId' => 'testUser'
            ],
            'POST',
            [
                'Content-Type' => 'application/json'
            ]
        );

        $expectedOptions = [
            'headers' => $logEvent->getHeaders(),
            'json' => $logEvent->getParams(),
            'timeout' => 10,
            'connect_timeout' => 10
        ];

        $guzzleClientMock = $this->getMockBuilder(HttpClient::class)
            ->getMock();

        $guzzleClientMock->expects($this->once())
            ->method('request')
            ->with($logEvent->getHttpVerb(), $logEvent->getUrl(), $expectedOptions);

        $eventDispatcher = new DefaultEventDispatcher($guzzleClientMock);
        $eventDispatcher->dispatchEvent($logEvent);
    }
}
