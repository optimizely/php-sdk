<?php
/**
 * Copyright 2016-2017, Optimizely
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
use Optimizely\Bucketer;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Event\Builder\EventBuilder;
use Optimizely\Event\LogEvent;
use Optimizely\Logger\NoOpLogger;
use Optimizely\ProjectConfig;


class EventBuilderTest extends \PHPUnit_Framework_TestCase
{
    private $testUserId;
    private $config;
    private $eventBuilder;

    public function setUp()
    {
        $this->testUserId = 'testUserId';
        $logger = new NoOpLogger();
        $this->config = new ProjectConfig(DATAFILE, $logger, new NoOpErrorHandler());
        $this->eventBuilder = new EventBuilder();
    }

    public function testCreateImpressionEventNoAttributes()
    {
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/log/decision',
            [
                'projectId' => '7720880029',
                'accountId' => '1592310167',
                'revision' => '15',
                'layerId' => '7719770039',
                'visitorId' => 'testUserId',
                'clientEngine' => 'php-sdk',
                'clientVersion' => '1.1.0',
                'timestamp' => time() * 1000,
                'isGlobalHoldback' => false,
                'userFeatures' => [],
                'decision' => [
                    'experimentId' => '7716830082',
                    'variationId' => '7721010009',
                    'isLayerHoldback' => false
                ]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );
        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            'test_experiment',
            'variation',
            $this->testUserId,
            null
        );

        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateImpressionEventWithAttributes()
    {
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/log/decision',
            [
                'projectId' => '7720880029',
                'accountId' => '1592310167',
                'revision' => '15',
                'layerId' => '7719770039',
                'visitorId' => 'testUserId',
                'clientEngine' => 'php-sdk',
                'clientVersion' => '1.1.0',
                'timestamp' => time() * 1000,
                'isGlobalHoldback' => false,
                'userFeatures' => [[
                    'id' => '7723280020',
                    'name' => 'device_type',
                    'type' => 'custom',
                    'value' => 'iPhone',
                    'shouldIndex' => true
                ]],
                'decision' => [
                    'experimentId' => '7716830082',
                    'variationId' => '7721010009',
                    'isLayerHoldback' => false
                ]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );

        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely'
        ];
        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            'test_experiment',
            'variation',
            $this->testUserId,
            $userAttributes
        );

        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateConversionEventNoAttributesNoValue()
    {
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/log/event',
            [
                'projectId' => '7720880029',
                'accountId' => '1592310167',
                'visitorId' => 'testUserId',
                'revision' => '15',
                'clientEngine' => 'php-sdk',
                'clientVersion' => '1.1.0',
                'userFeatures' => [],
                'isGlobalHoldback' => false,
                'timestamp' => time() * 1000,
                'eventFeatures' => [],
                'eventMetrics' => [],
                'eventEntityId' => '7718020063',
                'eventName' => 'purchase',
                'layerStates' => [[
                    'layerId' => '7719770039',
                    'actionTriggered' => true,
                    'revision' => '15',
                    'decision' =>  [
                        'experimentId' => '7716830082',
                        'variationId' => '7722370027',
                        'isLayerHoldback' => false
                    ]
                ]]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            ['test_experiment' => 'control'],
            $this->testUserId,
            null,
            null
        );

        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateConversionEventWithAttributesNoValue()
    {
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/log/event',
            [
                'projectId' => '7720880029',
                'accountId' => '1592310167',
                'visitorId' => 'testUserId',
                'revision' => '15',
                'clientEngine' => 'php-sdk',
                'clientVersion' => '1.1.0',
                'isGlobalHoldback' => false,
                'timestamp' => time() * 1000,
                'eventFeatures' => [],
                'eventMetrics' => [],
                'eventEntityId' => '7718020063',
                'eventName' => 'purchase',
                'layerStates' => [[
                    'layerId' => '7719770039',
                    'actionTriggered' => true,
                    'revision' => '15',
                    'decision' =>  [
                        'experimentId' => '7716830082',
                        'variationId' => '7722370027',
                        'isLayerHoldback' => false
                    ]
                ]],
                'userFeatures' => [[
                    'id' => '7723280020',
                    'name' => 'device_type',
                    'type' => 'custom',
                    'value' => 'iPhone',
                    'shouldIndex' => true
                ]]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );

        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely'
        ];
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            ['test_experiment' => 'control'],
            $this->testUserId,
            $userAttributes,
            null
        );

        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateConversionEventNoAttributesWithValue()
    {
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/log/event',
            [
                'projectId' => '7720880029',
                'accountId' => '1592310167',
                'visitorId' => 'testUserId',
                'revision' => '15',
                'clientEngine' => 'php-sdk',
                'clientVersion' => '1.1.0',
                'userFeatures' => [],
                'isGlobalHoldback' => false,
                'timestamp' => time() * 1000,
                'eventFeatures' => [
                    [
                        'name' => 'revenue',
                        'type' => 'custom',
                        'value' => 42,
                        'shouldIndex' => false
                    ]
                ],
                'eventMetrics' => [[
                    'name' => 'revenue',
                    'value' => 42
                ]],
                'eventEntityId' => '7718020063',
                'eventName' => 'purchase',
                'layerStates' => [[
                    'layerId' => '7719770039',
                    'actionTriggered' => true,
                    'revision' => '15',
                    'decision' =>  [
                        'experimentId' => '7716830082',
                        'variationId' => '7722370027',
                        'isLayerHoldback' => false
                    ]
                ]]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            ['test_experiment' => 'control'],
            $this->testUserId,
            null,
            array('revenue' => 42)
        );

        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateConversionEventWithAttributesWithValue()
    {
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/log/event',
            [
                'projectId' => '7720880029',
                'accountId' => '1592310167',
                'visitorId' => 'testUserId',
                'revision' => '15',
                'clientEngine' => 'php-sdk',
                'clientVersion' => '1.1.0',
                'isGlobalHoldback' => false,
                'timestamp' => time() * 1000,
                'eventFeatures' => [
                    [
                        'name' => 'revenue',
                        'type' => 'custom',
                        'value' => 42,
                        'shouldIndex' => false
                    ],
                    [
                        'name' => 'non-revenue',
                        'type' => 'custom',
                        'value' => 'definitely',
                        'shouldIndex' => false
                    ]
                ],
                'eventMetrics' => [[
                    'name' => 'revenue',
                    'value' => 42
                ]],
                'eventEntityId' => '7718020063',
                'eventName' => 'purchase',
                'layerStates' => [[
                    'layerId' => '7719770039',
                    'actionTriggered' => true,
                    'revision' => '15',
                    'decision' =>  [
                        'experimentId' => '7716830082',
                        'variationId' => '7722370027',
                        'isLayerHoldback' => false
                    ]
                ]],
                'userFeatures' => [[
                    'id' => '7723280020',
                    'name' => 'device_type',
                    'type' => 'custom',
                    'value' => 'iPhone',
                    'shouldIndex' => true
                ]]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );

        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely'
        ];
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            ['test_experiment' => 'control'],
            $this->testUserId,
            $userAttributes,
            array(
                'revenue' => 42,
                'non-revenue' => 'definitely'
            )
        );

        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateConversionEventNoAttributesWithInvalidValue()
    {
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/log/event',
            [
                'projectId' => '7720880029',
                'accountId' => '1592310167',
                'visitorId' => 'testUserId',
                'revision' => '15',
                'clientEngine' => 'php-sdk',
                'clientVersion' => '1.1.0',
                'userFeatures' => [],
                'isGlobalHoldback' => false,
                'timestamp' => time() * 1000,
                'eventFeatures' => [
                    [
                        'name' => 'revenue',
                        'type' => 'custom',
                        'value' => 42,
                        'shouldIndex' => false
                    ],
                    [
                        'name' => 'non-revenue',
                        'type' => 'custom',
                        'value' => 'definitely',
                        'shouldIndex' => false
                    ]
                ],
                'eventMetrics' => [],
                'eventEntityId' => '7718020063',
                'eventName' => 'purchase',
                'layerStates' => [[
                    'layerId' => '7719770039',
                    'actionTriggered' => true,
                    'revision' => '15',
                    'decision' =>  [
                        'experimentId' => '7716830082',
                        'variationId' => '7722370027',
                        'isLayerHoldback' => false
                    ]
                ]]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            ['test_experiment' => 'control'],
            $this->testUserId,
            null,
            array(
                'revenue' => '42',
                'non-revenue' => 'definitely'
            )
        );

        $this->assertEquals($expectedLogEvent, $logEvent);
    }
}
