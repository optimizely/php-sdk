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
use Optimizely\DecisionService\DecisionService;
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
    private $timestamp;
    private $uuid;

    public function setUp()
    {
        $this->testUserId = 'testUserId';
        $logger = new NoOpLogger();
        $this->config = new ProjectConfig(DATAFILE, $logger, new NoOpErrorHandler());
        $this->eventBuilder = new EventBuilder();
        $this->timestamp = time()*1000;
        $this->uuid = 'a68cf1ad-0393-4e18-af87-efe8f01a7c9c';
    }

    /**
     * Modifies timestamp and (randomly generated) uuid values to test
     * @param  LogEvent $logE To be tested log event
     * @return LogEvent      Modified log event
     */
    private function fakeParamsToReconcile($logE){
        $params = $logE->getParams();     
        if(isset($params['visitors'][0]['snapshots'][0]['events'][0]['timestamp']))
            $params['visitors'][0]['snapshots'][0]['events'][0]['timestamp'] = $this->timestamp;

        if(isset($params['visitors'][0]['snapshots'][0]['events'][0]['uuid']))
            $params['visitors'][0]['snapshots'][0]['events'][0]['uuid'] = $this->uuid;

        return new LogEvent($logE->getUrl(),$params,$logE->getHttpVerb(),$logE->getHeaders());       
    }

    public function testCreateImpressionEventNoAttributesNoValue()
    {
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7721010009',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7719770039',
                      'uuid'=> $this->uuid,
                      'key'=> 'campaign_activated'
                    ]
                  ]]
                ]],
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

        $logEvent = $this->fakeParamsToReconcile($logEvent);

        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateImpressionEventWithAttributesNoValue()
    {   
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [[
                    'entity_id' => '7723280020',
                    'key' => 'device_type',
                    'type' => 'custom',
                    'value' => 'iPhone',
                  ]],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7721010009',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7719770039',
                      'uuid'=> $this->uuid,
                      'key'=> 'campaign_activated'
                    ]
                  ]]
                ]],
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

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    /**
     * Should create proper params for getImpressionEvent with attributes as a false value
     */
    public function testCreateImpressionEventWithFalseAttributesNoValue()
    {   
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [[
                    'entity_id' => '7723280020',
                    'key' => 'device_type',
                    'type' => 'custom',
                    'value' => false,
                  ]],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7721010009',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7719770039',
                      'uuid'=> $this->uuid,
                      'key'=> 'campaign_activated'
                    ]
                  ]]
                ]],
                ]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );
        
        $userAttributes = [
            'device_type' => false,
            'company' => 'Optimizely'
        ];
        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            'test_experiment',
            'variation',
            $this->testUserId,
            $userAttributes
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    /**
     * Should create proper params for getImpressionEvent with attributes as a zero value
     */
    public function testCreateImpressionEventWithZeroAttributesNoValue()
    {   
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [[
                    'entity_id' => '7723280020',
                    'key' => 'device_type',
                    'type' => 'custom',
                    'value' => 0,
                  ]],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7721010009',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7719770039',
                      'uuid'=> $this->uuid,
                      'key'=> 'campaign_activated'
                    ]
                  ]]
                ]],
                ]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );
        
        $userAttributes = [
            'device_type' => 0,
            'company' => 'Optimizely'
        ];
        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            'test_experiment',
            'variation',
            $this->testUserId,
            $userAttributes
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    /**
     * Should not fill in userFeatures for getImpressionEvent when attribute is not in the datafile
     */
    public function testCreateImpressionEventWithInvalidAttributesNoValue()
    {   
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7721010009',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7719770039',
                      'uuid'=> $this->uuid,
                      'key'=> 'campaign_activated'
                    ]
                  ]]
                ]],
                ]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );
        
        $userAttributes = [
            'invalid_attribute' => 'sorry_not_sorry'
        ];
        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            'test_experiment',
            'variation',
            $this->testUserId,
            $userAttributes
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateConversionEventNoAttributesNoValue()
    {
         $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7721010009',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7718020063',
                      'uuid'=> $this->uuid,
                      'key'=> 'purchase'
                    ]
                  ]]
                ]],
                ]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            ['7716830082' => '7721010009'],
            $this->testUserId,
            null,
            null
        );
        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateConversionEventWithAttributesNoValue()
    {
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [[
                    'entity_id' => '7723280020',
                    'key' => 'device_type',
                    'type' => 'custom',
                    'value' => 'iPhone'
                   ]],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7722370027',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7718020063',
                      'uuid'=> $this->uuid,
                      'key'=> 'purchase'
                    ]
                  ]]
                ]],
                ]
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
            ['7716830082' => '7722370027'],
            $this->testUserId,
            $userAttributes,
            null
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $this->assertEquals($expectedLogEvent, $logEvent);
    }

     //Should not fill in userFeatures for getConversion when attribute is not in the datafile
     public function testCreateConversionEventInvalidAttributesNoValue()
    {
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7722370027',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7718020063',
                      'uuid'=> $this->uuid,
                      'key'=> 'purchase'
                    ]
                  ]]
                ]],
                ]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );
        
        $userAttributes = [
            'invalid_attribute'=> 'sorry_not_sorry'
        ];
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            ['7716830082' => '7722370027'],
            $this->testUserId,
            $userAttributes,
            null
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateConversionEventNoAttributesWithValue()
    {
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7722370027',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7718020063',
                      'uuid'=> $this->uuid,
                      'key'=> 'purchase',
                      'revenue' => 42,
                      'value'=> 13.37,
                      'tags' => [
                        'revenue' => 42,
                        'value' => '13.37'
                      ]
                    ]
                  ]]
                ]],
                ]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );
       
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            ['7716830082' => '7722370027'],
            $this->testUserId,
            null,
            array('revenue' => 42,'value'=> '13.37',)
        );
        $logEvent = $this->fakeParamsToReconcile($logEvent);

        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateConversionEventWithAttributesWithValue()
    {
         $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [[
                    'entity_id' => '7723280020',
                    'key' => 'device_type',
                    'type' => 'custom',
                    'value' => 'iPhone'
                   ]],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7722370027',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7718020063',
                      'uuid'=> $this->uuid,
                      'key'=> 'purchase',
                      'revenue' => 42,
                      'value' => 13.37,
                      'tags' => [
                        'revenue' => 42,
                        'non-revenue' => 'definitely',
                        'value'=> '13.37'
                      ]
                    ]
                  ]]
                ]],
                ]
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
            ['7716830082' => '7722370027'],
            $this->testUserId,
            $userAttributes,
            array(
                'revenue' => 42,
                'non-revenue' => 'definitely',
                'value'=> '13.37'
            )
        );
       
        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateConversionEventWithAttributesWithNumericTag()
    {
         $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [[
                    'entity_id' => '7723280020',
                    'key' => 'device_type',
                    'type' => 'custom',
                    'value' => 'iPhone'
                   ]],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7722370027',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7718020063',
                      'uuid'=> $this->uuid,
                      'key'=> 'purchase',
                      'value' => 13.37,
                      'tags' => [
                        'value'=> '13.37'
                      ]
                    ]
                  ]]
                ]],
                ]
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
            ['7716830082' => '7722370027'],
            $this->testUserId,
            $userAttributes,
            array(
                'value'=> '13.37'
            )
        );
       
        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateConversionEventNoAttributesWithInvalidValue()
    {
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7722370027',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7718020063',
                      'uuid'=> $this->uuid,
                      'key'=> 'purchase',
                      'tags' => [
                        'revenue' => '42',
                        'non-revenue' => 'definitely',
                        'value' => 'invalid value'
                      ]
                    ]
                  ]]
                ]],
                ]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );
        
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            ['7716830082' => '7722370027'],
            $this->testUserId,
            null,
            array(
                'revenue' => '42',
                'non-revenue' => 'definitely',
                'value' => 'invalid value'
            )
        );
 
        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    // Bucketing ID should be part of the impression event sans the ID
    // (since this custom attribute entity is not generated by GAE)
    public function testCreateImpressionEventWithBucketingIDAttribute()
    {
         $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [[
                    'entity_id' => '7723280020',
                    'key' => 'device_type',
                    'type' => 'custom',
                    'value' => 'iPhone',
                  ],[
                    'entity_id' => RESERVED_ATTRIBUTE_KEY_BUCKETING_ID,
                    'key' => RESERVED_ATTRIBUTE_KEY_BUCKETING_ID_EVENT_PARAM_KEY,
                    'type' => 'custom',
                    'value' => 'variation',
                  ]],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7721010009',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7719770039',
                      'uuid'=> $this->uuid,
                      'key'=> 'campaign_activated'
                    ]
                  ]]
                ]],
                ]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );

        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            RESERVED_ATTRIBUTE_KEY_BUCKETING_ID => 'variation'
        ];
        $logEvent = $this->eventBuilder->createImpressionEvent(
            $this->config,
            'test_experiment',
            'variation',
            $this->testUserId,
            $userAttributes
        );

     
        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $this->assertEquals($expectedLogEvent, $logEvent);
    }

    public function testCreateConversionEventWithBucketingIDAttribute()
    {
        $expectedLogEvent = new LogEvent(
            'https://logx.optimizely.com/v1/events',
            [
                'project_id' => '7720880029',
                'account_id' => '1592310167',
                'anonymize_ip'=> false,
                'revision' => '15',
                'client_name' => 'php-sdk',
                'client_version' => '1.4.0',
                'visitors'=> [[
                  'attributes'=> [[
                    'entity_id' => '7723280020',
                    'key' => 'device_type',
                    'type' => 'custom',
                    'value' => 'iPhone',
                  ],[
                    'entity_id' => RESERVED_ATTRIBUTE_KEY_BUCKETING_ID,
                    'key' => RESERVED_ATTRIBUTE_KEY_BUCKETING_ID_EVENT_PARAM_KEY,
                    'type' => 'custom',
                    'value' => 'variation',
                  ]],
                  'visitor_id'=> 'testUserId',
                  'snapshots'=> [[
                    'decisions'=> [[
                      'variation_id'=> '7722370027',
                      'experiment_id'=> '7716830082',
                      'campaign_id'=> '7719770039'
                    ]],
                    'events'=> [[
                      'timestamp'=> $this->timestamp,
                      'entity_id'=> '7718020063',
                      'uuid'=> $this->uuid,
                      'key'=> 'purchase'
                    ]
                  ]]
                ]],
                ]
            ],
            'POST',
            ['Content-Type' => 'application/json']
        );

        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            RESERVED_ATTRIBUTE_KEY_BUCKETING_ID => 'variation'
        ];
        $logEvent = $this->eventBuilder->createConversionEvent(
            $this->config,
            'purchase',
            ['7716830082' => '7722370027'],
            $this->testUserId,
            $userAttributes,
            null
        );

        $logEvent = $this->fakeParamsToReconcile($logEvent);
        $this->assertEquals($expectedLogEvent, $logEvent);
    }
}
