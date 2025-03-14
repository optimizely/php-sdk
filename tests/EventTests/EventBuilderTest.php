<?php
/**
 * Copyright 2016-2021, 2023, Optimizely
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

use Icecave\Parity\Parity;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Differ;
use Optimizely\Bucketer;
use Optimizely\Config\DatafileProjectConfig;
use Optimizely\DecisionService\DecisionService;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Event\Builder\EventBuilder;
use Optimizely\Event\LogEvent;
use Optimizely\Logger\NoOpLogger;

class EventBuilderTest extends TestCase
{
    private $testUserId;
    private $config;
    private $eventBuilder;
    private $timestamp;
    private $uuid;

    protected function setUp() : void
    {
        $this->testUserId = 'testUserId';
        $logger = new NoOpLogger();
        $this->config = new DatafileProjectConfig(DATAFILE, $logger, new NoOpErrorHandler());
        $this->eventBuilder = new EventBuilder($logger);
        $this->timestamp = time()*1000;
        $this->uuid = 'a68cf1ad-0393-4e18-af87-efe8f01a7c9c';
        $this->differ = new Differ();

        $this->expectedEventUrl = 'https://logx.optimizely.com/v1/events';
        $this->expectedEventParams = [
                'account_id' => '1592310167',
                'project_id' => '7720880029',
                'visitors'=> [[
                  'snapshots'=> [[
                    'events'=> [[
                      'entity_id'=> '7719770039',
                      'timestamp'=> $this->timestamp,
                      'uuid'=> $this->uuid,
                      'key'=> 'campaign_activated'
                    ]]
                  ]],
                  'visitor_id'=> 'testUserId',
                  'attributes'=> [[
                    'entity_id' => '$opt_bot_filtering',
                    'key' => '$opt_bot_filtering',
                    'type' => 'custom',
                    'value' => true
                  ]]
                ]],
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '4.0.2',
                'anonymize_ip'=> false,
                'enrich_decisions' => true,
            ];

        $decisions = array('decisions' => [[
                              'campaign_id'=> '7719770039',
                              'experiment_id'=> '7716830082',
                              'variation_id'=> '7721010009',
                              'metadata'=> [
                                  'flag_key' => 'test_experiment',
                                  'rule_key' => 'test_experiment',
                                  'rule_type' => 'experiment',
                                  'variation_key'=> 'variation',
                                  'enabled' => true
                              ]
                            ]]
                          );
        $this->expectedImpressionEventParams = $this->expectedEventParams;
        $this->expectedImpressionEventParams['visitors'][0]['snapshots'][0] = $decisions +
                                                                    $this->expectedImpressionEventParams['visitors'][0]['snapshots'][0];
        $this->expectedEventHttpVerb = 'POST';
        $this->expectedEventHeaders = ['Content-Type' => 'application/json'];
    }

    /**
     * Performs Deep Strict Comparison of two objects
     *
     * @param  LogEvent $e1
     * @param  LogEvent $e2
     * @return [Boolean,String]  [True,""] when equal, otherwise [False,"diff-string"]
     */
    private function areLogEventsEqual($e1, $e2)
    {
        $msg = "";
        $isEqual = Parity::isEqualTo($e1, $e2);
        if (!$isEqual) {
            $msg = $this->differ->diff(var_export($e1, true), var_export($e2, true));
        }

        return [$isEqual,$msg];
    }

    /**
     * Modifies timestamp and (randomly generated) uuid values to test
     *
     * @param  LogEvent $logE To be tested log event
     * @return LogEvent      Modified log event
     */
    private function fakeParamsToReconcile($logE)
    {
        $params = $logE->getParams();
        if (isset($params['visitors'][0]['snapshots'][0]['events'][0]['timestamp'])) {
            $params['visitors'][0]['snapshots'][0]['events'][0]['timestamp'] = $this->timestamp;
        }

        if (isset($params['visitors'][0]['snapshots'][0]['events'][0]['uuid'])) {
            $params['visitors'][0]['snapshots'][0]['events'][0]['uuid'] = $this->uuid;
        }

        return new LogEvent($logE->getUrl(), $params, $logE->getHttpVerb(), $logE->getHeaders());
    }

    public function testCreateImpressionEventNoAttributesNoValue()
    {
        $this->expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedImpressionEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            '7716830082',
            'variation',
            'test_experiment',
            'test_experiment',
            'experiment',
            true,
            $this->testUserId,
            null
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($this->expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateImpressionEventVariationFromSameFlagButDifferentExperiments()
    {
        $decisions = array('decisions' => [[
            'campaign_id'=> '5',
            'experiment_id'=> '122235',
            'variation_id'=> '177775',
            'metadata'=> [
                'flag_key' => 'string_single_variable_feature',
                'rule_key' => 'test_experiment_with_feature_rollout',
                'rule_type' => 'experiment',
                'variation_key'=> '177775',
                'enabled' => true
            ]
          ]]
        );
        $this->expectedImpressionEventParams['visitors'][0]['snapshots'][0] = $decisions +
                                                                    $this->expectedImpressionEventParams['visitors'][0]['snapshots'][0];
        $this->expectedImpressionEventParams['visitors'][0]['snapshots'][0]['events'][0] =
        [
            'entity_id'=> '5',
            'timestamp'=> $this->timestamp,
            'uuid'=> $this->uuid,
            'key'=> 'campaign_activated'
        ];
        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedImpressionEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            '122235',
            '177775',
            'string_single_variable_feature',
            'test_experiment_with_feature_rollout',
            'experiment',
            true,
            $this->testUserId,
            null
        );
        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateImpressionEventWithAttributesNoValue()
    {
        array_unshift(
            $this->expectedImpressionEventParams['visitors'][0]['attributes'],
            [
                'entity_id' => '7723280020',
                'key' => 'device_type',
                'type' => 'custom',
                'value' => 'iPhone',
            ],
            [
                'entity_id' => '7723340007',
                'key' => 'boolean_key',
                'type' => 'custom',
                'value' => true
            ],
            [
                'entity_id' => '7723340008',
                'key' => 'double_key',
                'type' => 'custom',
                'value' => 5.5
            ],
            [
                'entity_id' => '7723340009',
                'key' => 'integer_key',
                'type' => 'custom',
                'value' => 5
            ]
        );

        $this->expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedImpressionEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $userAttributes = [
            'device_type' => 'iPhone',
            'boolean_key' => true,
            'double_key' => 5.5,
            'integer_key' => 5
        ];
        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            '7716830082',
            'variation',
            'test_experiment',
            'test_experiment',
            'experiment',
            true,
            $this->testUserId,
            $userAttributes
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($this->expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    /**
     * Should create proper params for getImpressionEvent with attributes as a false value
     */
    public function testCreateImpressionEventWithFalseAttributesNoValue()
    {
        array_unshift(
            $this->expectedImpressionEventParams['visitors'][0]['attributes'],
            [ 'entity_id' => '7723280020',
            'key' => 'device_type',
            'type' => 'custom',
            'value' => 'false',
            ]
        );

        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedImpressionEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );
        $userAttributes = [
            'device_type' => 'false',
            'company' => 'Optimizely'
        ];
        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            '7716830082',
            'variation',
            'test_experiment',
            'test_experiment',
            'experiment',
            true,
            $this->testUserId,
            $userAttributes
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    /**
     * Should create proper params for getImpressionEvent with attributes as a zero value
     */
    public function testCreateImpressionEventWithZeroAttributesNoValue()
    {
        array_unshift(
            $this->expectedImpressionEventParams['visitors'][0]['attributes'],
            [ 'entity_id' => '7723280020',
            'key' => 'device_type',
            'type' => 'custom',
            'value' => 0,
            ]
        );

        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedImpressionEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $userAttributes = [
            'device_type' => 0,
            'company' => 'Optimizely'
        ];
        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            '7716830082',
            'variation',
            'test_experiment',
            'test_experiment',
            'experiment',
            true,
            $this->testUserId,
            $userAttributes
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    /**
     * Should not fill in userFeatures for getImpressionEvent when attribute is not in the datafile
     */
    public function testCreateImpressionEventWithInvalidAttributesNoValue()
    {
        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedImpressionEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $userAttributes = [
            'invalid_attribute' => 'sorry_not_sorry'
        ];
        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            '7716830082',
            'variation',
            'test_experiment',
            'test_experiment',
            'experiment',
            true,
            $this->testUserId,
            $userAttributes
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateImpressionEventWithUserAgentWhenBotFilteringIsEnabled()
    {
        $this->expectedImpressionEventParams['visitors'][0]['attributes'] =
          [[
            'entity_id' => '$opt_user_agent',
            'key' => '$opt_user_agent',
            'type' => 'custom',
            'value' => 'Edge',
          ],[
            'entity_id' => '$opt_bot_filtering',
            'key' => '$opt_bot_filtering',
            'type' => 'custom',
            'value' => true]];

        $this->expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedImpressionEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $userAttributes = [
            '$opt_user_agent' => 'Edge',
        ];
        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            '7716830082',
            'variation',
            'test_experiment',
            'test_experiment',
            'experiment',
            true,
            $this->testUserId,
            $userAttributes
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($this->expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateImpressionEventWithInvalidAttributeTypes()
    {
        array_unshift(
            $this->expectedImpressionEventParams['visitors'][0]['attributes'],
            [
                'entity_id' => '7723280020',
                'key' => 'device_type',
                'type' => 'custom',
                'value' => 'iPhone',
            ],
            [
                'entity_id' => '7723340008',
                'key' => 'double_key',
                'type' => 'custom',
                'value' => 5.5
            ]
        );

        $this->expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedImpressionEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $userAttributes = [
            'device_type' => 'iPhone',
            'boolean_key' => null,
            'double_key' => 5.5,
            'integer_key' => []
        ];
        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            '7716830082',
            'variation',
            'test_experiment',
            'test_experiment',
            'experiment',
            true,
            $this->testUserId,
            $userAttributes
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($this->expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateImpressionEventWithUserAgentWhenBotFilteringIsDisabled()
    {
        $this->expectedImpressionEventParams['visitors'][0]['attributes'] =
          [[
            'entity_id' => '$opt_user_agent',
            'key' => '$opt_user_agent',
            'type' => 'custom',
            'value' => 'Chrome',
          ],[
            'entity_id' => '$opt_bot_filtering',
            'key' => '$opt_bot_filtering',
            'type' => 'custom',
            'value' => false]];

        $this->expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedImpressionEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $userAttributes = [
            '$opt_user_agent' => 'Chrome',
        ];

        $configMock = $this->getMockBuilder(DatafileProjectConfig::class)
            ->setConstructorArgs(array(DATAFILE, new NoOpLogger, new NoOpErrorHandler))
            ->setMethods(array('getBotFiltering'))
            ->getMock();

        $configMock
            ->method('getBotFiltering')
            ->will($this->returnValue(false));

        $logEvent = $this->eventBuilder->createImpressionEvent(
            $configMock,
            '7716830082',
            'variation',
            'test_experiment',
            'test_experiment',
            'experiment',
            true,
            $this->testUserId,
            $userAttributes
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($this->expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateImpressionEventWithUserAgentWhenBotFilteringIsNull()
    {
        $this->expectedImpressionEventParams['visitors'][0]['attributes'] =
          [[
            'entity_id' => '$opt_user_agent',
            'key' => '$opt_user_agent',
            'type' => 'custom',
            'value' => 'Chrome',
          ]];

        $this->expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedImpressionEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $userAttributes = [
            '$opt_user_agent' => 'Chrome',
        ];

        $configMock = $this->getMockBuilder(DatafileProjectConfig::class)
            ->setConstructorArgs(array(DATAFILE, new NoOpLogger, new NoOpErrorHandler))
            ->setMethods(array('getBotFiltering'))
            ->getMock();

        $configMock
            ->method('getBotFiltering')
            ->will($this->returnValue(null));

        $logEvent = $this->eventBuilder->createImpressionEvent(
            $configMock,
            '7716830082',
            'variation',
            'test_experiment',
            'test_experiment',
            'experiment',
            true,
            $this->testUserId,
            $userAttributes
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($this->expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateConversionEventNoAttributesNoValue()
    {
        $this->expectedEventParams['visitors'][0]['snapshots'][0]['events'][0] =
          [
            'entity_id'=> '7718020063',
            'timestamp'=> $this->timestamp,
            'uuid'=> $this->uuid,
            'key'=> 'purchase'
          ];
        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            $this->testUserId,
            null,
            null
        );
        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateConversionEventWithAttributesNoValue()
    {
        array_unshift(
            $this->expectedEventParams['visitors'][0]['attributes'],
            [ 'entity_id' => '7723280020',
            'key' => 'device_type',
            'type' => 'custom',
            'value' => 'iPhone',
            ]
        );

        $this->expectedEventParams['visitors'][0]['snapshots'][0]['events'][0] =
          [
            'entity_id'=> '7718020063',
            'timestamp'=> $this->timestamp,
            'uuid'=> $this->uuid,
            'key'=> 'purchase'
          ];

        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely'
        ];
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            $this->testUserId,
            $userAttributes,
            null
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    //Should not fill in userFeatures for getConversion when attribute is not in the datafile
    public function testCreateConversionEventInvalidAttributesNoValue()
    {
        $this->expectedEventParams['visitors'][0]['snapshots'][0]['events'][0] =
          [
            'entity_id'=> '7718020063',
            'timestamp'=> $this->timestamp,
            'uuid'=> $this->uuid,
            'key'=> 'purchase'
          ];

        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $userAttributes = [
            'invalid_attribute'=> 'sorry_not_sorry'
        ];
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            $this->testUserId,
            $userAttributes,
            null
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateConversionEventNoAttributesWithValue()
    {
        $this->expectedEventParams['visitors'][0]['snapshots'][0]['events'][0] =
           [
            'entity_id'=> '7718020063',
            'timestamp'=> $this->timestamp,
            'uuid'=> $this->uuid,
            'key'=> 'purchase',
            'revenue' => 42,
            'value'=> 13.37,
            'tags' => [
              'revenue' => 42,
              'value' => '13.37'
            ]
           ];

        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            $this->testUserId,
            null,
            array('revenue' => 42,'value'=> '13.37',)
        );
        $logEvent = $this->fakeParamsToReconcile($logEvent);

        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateConversionEventWithAttributesWithValue()
    {
        array_unshift(
            $this->expectedEventParams['visitors'][0]['attributes'],
            [ 'entity_id' => '7723280020',
            'key' => 'device_type',
            'type' => 'custom',
            'value' => 'iPhone',
            ]
        );

        $this->expectedEventParams['visitors'][0]['snapshots'][0]['events'][0] =
           [
            'entity_id'=> '7718020063',
            'timestamp'=> $this->timestamp,
            'uuid'=> $this->uuid,
            'key'=> 'purchase',
            'revenue' => 42,
            'value'=> 13.37,
            'tags' => [
              'revenue' => 42,
              'non-revenue' => 'definitely',
              'value' => '13.37'
            ]
           ];

        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely'
        ];
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            $this->testUserId,
            $userAttributes,
            array(
                'revenue' => 42,
                'non-revenue' => 'definitely',
                'value'=> '13.37'
            )
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateConversionEventWithAttributesWithNumericTag()
    {
        array_unshift(
            $this->expectedEventParams['visitors'][0]['attributes'],
            [ 'entity_id' => '7723280020',
            'key' => 'device_type',
            'type' => 'custom',
            'value' => 'iPhone',
            ]
        );

        $this->expectedEventParams['visitors'][0]['snapshots'][0]['events'][0] =
           [
            'entity_id'=> '7718020063',
            'timestamp'=> $this->timestamp,
            'uuid'=> $this->uuid,
            'key'=> 'purchase',
            'value'=> 13.37,
            'tags' => [
              'value' => '13.37'
            ]
           ];

        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );
        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely'
        ];
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            $this->testUserId,
            $userAttributes,
            array(
                'value'=> '13.37'
            )
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateConversionEventNoAttributesWithInvalidValue()
    {
        $this->expectedEventParams['visitors'][0]['snapshots'][0]['events'][0] =
           [
            'entity_id'=> '7718020063',
            'timestamp'=> $this->timestamp,
            'uuid'=> $this->uuid,
            'key'=> 'purchase',
            'tags' => [
              'revenue' => '42.5',
              'non-revenue' => 'definitely',
              'value' => 'invalid value'
            ]
           ];

        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            $this->testUserId,
            null,
            array(
                'revenue' => '42.5',
                'non-revenue' => 'definitely',
                'value' => 'invalid value'
            )
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    // Bucketing ID should be part of the impression event sans the ID
    // (since this custom attribute entity is not generated by GAE)
    public function testCreateImpressionEventWithBucketingIDAttribute()
    {
        array_unshift(
            $this->expectedImpressionEventParams['visitors'][0]['attributes'],
            [
            'entity_id' => '7723280020',
            'key' => 'device_type',
            'type' => 'custom',
            'value' => 'iPhone'
            ],
            [
            'entity_id' => '$opt_bucketing_id',
            'key' => '$opt_bucketing_id',
            'type' => 'custom',
            'value' => 'variation'
            ]
        );

        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedImpressionEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            '$opt_bucketing_id' => 'variation'
        ];
        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            '7716830082',
            'variation',
            'test_experiment',
            'test_experiment',
            'experiment',
            true,
            $this->testUserId,
            $userAttributes
        );


        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateConversionEventWithBucketingIDAttribute()
    {
        array_unshift(
            $this->expectedEventParams['visitors'][0]['attributes'],
            [
            'entity_id' => '7723280020',
            'key' => 'device_type',
            'type' => 'custom',
            'value' => 'iPhone',
            ],
            [
            'entity_id' => '$opt_bucketing_id',
            'key' => '$opt_bucketing_id',
            'type' => 'custom',
            'value' => 'variation',
            ]
        );

        $this->expectedEventParams['visitors'][0]['snapshots'][0]['events'][0] =
           [
            'entity_id'=> '7718020063',
            'timestamp'=> $this->timestamp,
            'uuid'=> $this->uuid,
            'key'=> 'purchase'
           ];

        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            '$opt_bucketing_id' => 'variation'
        ];

        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            $this->testUserId,
            $userAttributes,
            null
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateConversionEventWithUserAgentAttribute()
    {
        array_unshift(
            $this->expectedEventParams['visitors'][0]['attributes'],
            [ 'entity_id' => '$opt_user_agent',
            'key' => '$opt_user_agent',
            'type' => 'custom',
            'value' => 'Firefox',
            ]
        );

        $this->expectedEventParams['visitors'][0]['snapshots'][0]['events'][0] =
        [
          'entity_id'=> '7718020063',
          'timestamp'=> $this->timestamp,
          'uuid'=> $this->uuid,
          'key'=> 'purchase'
        ];

        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $userAttributes = [
          '$opt_user_agent' => 'Firefox'
        ];
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            $this->testUserId,
            $userAttributes,
            null
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }

    public function testCreateConversionEventWhenEventUsedInMultipleExp()
    {
        $eventTags = [
            'revenue' => 4200,
            'value' => 1.234,
            'non-revenue' => 'abc'
        ];

        $this->expectedEventParams['visitors'][0]['snapshots'][0]['events'][0] = [
            'entity_id' => '7718020065',
            'timestamp' => $this->timestamp,
            'uuid' => $this->uuid,
            'key' => 'multi_exp_event',
            'revenue' => 4200,
            'value' => 1.234,
            'tags' => $eventTags,
        ];

        array_unshift(
            $this->expectedEventParams['visitors'][0]['attributes'],
            [
                'entity_id' => '7723280020',
                'key' => 'device_type',
                'type' => 'custom',
                'value' => 'iPhone'
            ],
            [
                'entity_id' => '7723340004',
                'key' => 'location',
                'type' => 'custom',
                'value' => 'SF'
            ]
        );

        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'SF'
        ];

        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'multi_exp_event',
            $this->testUserId,
            $userAttributes,
            $eventTags
        );
        $expectedLogEvent = new LogEvent(
            $this->expectedEventUrl,
            $this->expectedEventParams,
            $this->expectedEventHttpVerb,
            $this->expectedEventHeaders
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $result = $this->areLogEventsEqual($expectedLogEvent, $logEvent);
        $this->assertTrue($result[0], $result[1]);
    }
}
