<?php
/**
 * Copyright 2019, Optimizely
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

use Optimizely\Event\Builder\EventBuilder;
use Optimizely\Event\Dispatcher\CurlEventDispatcher;
use Optimizely\Event\LogEvent;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Logger\NoOpLogger;
use Optimizely\ProjectConfig;

class CurlEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $logger = new NoOpLogger();
        $this->config = new ProjectConfig(DATAFILE, $logger, new NoOpErrorHandler());
        $this->eventBuilder = new EventBuilder($logger);
    }

   // Test that user input values are escaped. This includes
   // 1. user ID.
   // 2. attribute values of type string.
   // 3. event tag keys.
   // 4. event tag values of type string.
    public function testSanitizeEventPayloadWithEventTags()
    {
        $eventDispatcher = new CurlEventDispatcher();

        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
        ];

        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            'testUserId',
            $userAttributes,
            array(
                'revenue' => 56,
                'value'=> '13.37',
                'boolean-tag' => false,
                'float' => 5.5,
                'integer' => 6
            )
        );

        $expectedParams = $logEvent->getParams();

        $expectedUserId = escapeshellarg('testUserId');
        $expectedParams['visitors'][0]['visitor_id'] = $expectedUserId;

        $expectedAttributes = $expectedParams['visitors'][0]['attributes'];
        foreach ($expectedAttributes as &$attr) {
            if (in_array($attr['key'], ['device_type', 'company'])) {
                $attr['value'] = escapeshellarg($attr['value']);
            }
        }

        $expectedParams['visitors'][0]['attributes'] = $expectedAttributes;

        $expectedEventTags = [];
        $expectedEventTags[escapeshellarg('revenue')] = 56;
        $expectedEventTags[escapeshellarg('value')] = escapeshellarg('13.37');
        $expectedEventTags[escapeshellarg('boolean-tag')] = false;
        $expectedEventTags[escapeshellarg('float')] = 5.5;
        $expectedEventTags[escapeshellarg('integer')] = 6;

        $expectedParams['visitors'][0]['snapshots'][0]['events'][0]['tags'] = $expectedEventTags;

        $this->assertEquals($expectedParams, $eventDispatcher->sanitizeEventPayload($logEvent->getParams()));
    }
}
