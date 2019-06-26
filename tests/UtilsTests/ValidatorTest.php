<?php
/**
 * Copyright 2016-2019, Optimizely
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

use Monolog\Logger;
use Optimizely\Entity\Audience;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Config\DatafileProjectConfig;
use Optimizely\Utils\Validator;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected $loggerMock;

    protected function setUp()
    {
        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();
    }

    public function testValidateJsonSchemaValidFile()
    {
        $this->assertTrue(Validator::validateJsonSchema(DATAFILE));
    }

    public function testValidateJsonSchemaInvalidFile()
    {
        $invalidDatafile = '{"key1": "val1"}';
        $this->assertFalse(Validator::validateJsonSchema($invalidDatafile));
    }

    public function testValidateJsonSchemaNoJsonContent()
    {
        $invalidDatafile = 'some random file.';
        $this->assertFalse(Validator::validateJsonSchema($invalidDatafile));
    }

    public function testValidateJsonSchemaInvalidJsonWithLogger()
    {
        $invalidDatafile = '{"key1": "val1"}';
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, "JSON does not validate. Violations:\n");
        $this->assertFalse(Validator::validateJsonSchema($invalidDatafile, $this->loggerMock));
    }

    public function testAreAttributesValidValidAttributes()
    {
        // Empty attributes
        $this->assertTrue(Validator::areAttributesValid([]));

        // Mixed array as attributes
        $this->assertTrue(Validator::areAttributesValid([0, 1, 2, 42, 'abc' => 'def']));

        // Valid attributes
        $this->assertTrue(
            Validator::areAttributesValid(
                [
                    'location' => 'San Francisco',
                    'browser' => 'Firefox',
                    'boolean'=> true,
                    'double'=> 5.5,
                    'integer'=> 5
                ]
            )
        );
    }

    public function testAreAttributesValidInvalidAttributes()
    {
        // String as attributes
        $this->assertFalse(Validator::areAttributesValid('Invalid string attributes.'));

        // Integer as attributes
        $this->assertFalse(Validator::areAttributesValid(42));

        // Boolean as attributes
        $this->assertFalse(Validator::areAttributesValid(true));

        // Sequential array as attributes
        $this->assertFalse(Validator::areAttributesValid([0, 1, 2, 42]));
    }

    public function testisAttributeValidAttributeWithValidKeyValue()
    {
        $this->assertTrue(Validator::isAttributeValid('key', 'value'));
        $this->assertTrue(Validator::isAttributeValid('key', 5));
        $this->assertTrue(Validator::isAttributeValid('key', 5.5));
        $this->assertTrue(Validator::isAttributeValid('key', true));
        $this->assertTrue(Validator::isAttributeValid('key', false));
        $this->assertTrue(Validator::isAttributeValid('key', 0));
        $this->assertTrue(Validator::isAttributeValid('key', 0.0));
        $this->assertTrue(Validator::isAttributeValid('', 0.0));
    }

    public function testisAttributeValidAttributeWithInValidKeyValue()
    {
        # Invalid Value
        $this->assertFalse(Validator::isAttributeValid('key', null));
        $this->assertFalse(Validator::isAttributeValid('key', []));
        $this->assertFalse(Validator::isAttributeValid('key', ['key'=>'value']));
        # Invalid Key
        $this->assertFalse(Validator::isAttributeValid(null, 'value'));
        $this->assertFalse(Validator::isAttributeValid([], 'value'));
        $this->assertFalse(Validator::isAttributeValid(5, 'value'));
        $this->assertFalse(Validator::isAttributeValid(5.5, 'value'));
    }

    public function testIsFiniteNumberWithInvalidValues()
    {
        $this->assertFalse(Validator::IsFiniteNumber('HelloWorld'));
        $this->assertFalse(Validator::IsFiniteNumber(true));
        $this->assertFalse(Validator::IsFiniteNumber(false));
        $this->assertFalse(Validator::IsFiniteNumber(null));
        $this->assertFalse(Validator::IsFiniteNumber((object)[]));
        $this->assertFalse(Validator::IsFiniteNumber([]));
        $this->assertFalse(Validator::IsFiniteNumber(INF));
        $this->assertFalse(Validator::IsFiniteNumber(-INF));
        $this->assertFalse(Validator::IsFiniteNumber(NAN));
        $this->assertFalse(Validator::IsFiniteNumber(pow(2, 53) + 1));
        $this->assertFalse(Validator::IsFiniteNumber(-pow(2, 53) - 1));
        $this->assertFalse(Validator::IsFiniteNumber(pow(2, 53) + 2.0));
        $this->assertFalse(Validator::IsFiniteNumber(-pow(2, 53) - 2.0));
    }

    public function testIsFiniteNumberWithValidValues()
    {
        $this->assertTrue(Validator::IsFiniteNumber(0));
        $this->assertTrue(Validator::IsFiniteNumber(5));
        $this->assertTrue(Validator::IsFiniteNumber(5.5));
        // float pow(2,53) + 1.0 evaluates to float pow(2,53)
        $this->assertTrue(Validator::IsFiniteNumber(pow(2, 53) + 1.0));
        $this->assertTrue(Validator::IsFiniteNumber(-pow(2, 53) - 1.0));
        $this->assertTrue(Validator::IsFiniteNumber(pow(2, 53)));
    }

    public function testAreEventTagsValidValidEventTags()
    {
        // Empty attributes
        $this->assertTrue(Validator::areEventTagsValid([]));

        // Valid attributes
        $this->assertTrue(
            Validator::areEventTagsValid(
                [
                'revenue' => 0,
                'location' => 'San Francisco',
                'browser' => 'Firefox'
                ]
            )
        );
    }

    public function testAreEventTagsValidInvalidEventTags()
    {
        // String as attributes
        $this->assertFalse(Validator::areEventTagsValid('Invalid string attributes.'));

        // Integer as attributes
        $this->assertFalse(Validator::areEventTagsValid(42));

        // Boolean as attributes
        $this->assertFalse(Validator::areEventTagsValid(true));

        // Sequential array as attributes
        $this->assertFalse(Validator::areEventTagsValid([0, 1, 2, 42]));

        // Mixed array as attributes
        $this->assertFalse(Validator::areEventTagsValid([0, 1, 2, 42, 'abc' => 'def']));
    }

    // test that Audience evaluation proceeds if provided attributes are empty or null.
    public function testIsUserInExperimentAudienceUsedInExperimentNoAttributesProvided()
    {
        $configMock = $this->getMockBuilder(DatafileProjectConfig::class)
            ->setConstructorArgs(array(DATAFILE, $this->loggerMock, new NoOpErrorHandler()))
            ->setMethods(array('getAudience'))
            ->getMock();

        $existsCondition = [
            'type' => 'custom_attribute',
            'name' => 'input_value',
            'match' => 'exists',
            'value' => null
        ];

        $experiment = $configMock->getExperimentFromKey('test_experiment');
        $experiment->setAudienceIds(['007']);
        $audience = new Audience();
        $audience->setConditionsList(['not', $existsCondition]);

        $configMock
            ->method('getAudience')
            ->with('007')
            ->will($this->returnValue($audience));

        $this->assertTrue(
            Validator::isUserInExperiment(
                $configMock,
                $experiment,
                null,
                $this->loggerMock
            )
        );

        $this->assertTrue(
            Validator::isUserInExperiment(
                $configMock,
                $experiment,
                [],
                $this->loggerMock
            )
        );
    }

    public function testIsUserInExperimentAudienceMatch()
    {
        $config = new DatafileProjectConfig(DATAFILE, new NoOpLogger(), new NoOpErrorHandler());
        $this->assertTrue(
            Validator::isUserInExperiment(
                $config,
                $config->getExperimentFromKey('test_experiment'),
                ['device_type' => 'iPhone', 'location' => 'San Francisco'],
                $this->loggerMock
            )
        );
    }

    public function testIsUserInExperimentAudienceNoMatch()
    {
        $config = new DatafileProjectConfig(DATAFILE, new NoOpLogger(), new NoOpErrorHandler());
        $this->assertFalse(
            Validator::isUserInExperiment(
                $config,
                $config->getExperimentFromKey('test_experiment'),
                ['device_type' => 'Android', 'location' => 'San Francisco'],
                $this->loggerMock
            )
        );
    }

    // test that isUserInExperiment returns true when no audience is attached to experiment.
    public function testIsUserInExperimentNoAudienceUsedInExperiment()
    {
        $config = new DatafileProjectConfig(DATAFILE, null, null);
        $experiment = $config->getExperimentFromKey('test_experiment');

        // Both audience conditions and audience Ids are empty.
        $experiment->setAudienceIds([]);
        $experiment->setAudienceConditions([]);
        $this->assertTrue(
            Validator::isUserInExperiment(
                $config,
                $experiment,
                [],
                $this->loggerMock
            )
        );

        // Audience Ids exist but audience conditions is empty.
        $experiment->setAudienceIds(['7718080042']);
        $experiment->setAudienceConditions([]);
        $this->assertTrue(
            Validator::isUserInExperiment(
                $config,
                $experiment,
                [],
                $this->loggerMock
            )
        );

        // Audience Ids is empty and audience conditions is null.
        $experiment->setAudienceIds([]);
        $experiment->setAudienceConditions(null);
        $this->assertTrue(
            Validator::isUserInExperiment(
                $config,
                $experiment,
                [],
                $this->loggerMock
            )
        );
    }

    // test that isUserInExperiment returns false when some audience is attached to experiment
    // and user attributes do not match.
    public function testIsUserInExperimentSomeAudienceUsedInExperiment()
    {
        $config = new DatafileProjectConfig(DATAFILE, null, null);
        $experiment = $config->getExperimentFromKey('test_experiment');

        // Both audience Ids and audience conditions exist. Audience Ids is ignored.
        $experiment->setAudienceIds(['7718080042']);
        $experiment->setAudienceConditions(['11155']);

        $this->assertFalse(
            Validator::isUserInExperiment(
                $config,
                $experiment,
                ['device_type' => 'Android', 'location' => 'San Francisco'],
                $this->loggerMock
            )
        );

        // Audience Ids exist and audience conditions is null.
        $experiment = $config->getExperimentFromKey('test_experiment');
        $experiment->setAudienceIds(['11155']);
        $experiment->setAudienceConditions(null);

        $this->assertFalse(
            Validator::isUserInExperiment(
                $config,
                $experiment,
                ['device_type' => 'iPhone', 'location' => 'San Francisco'],
                $this->loggerMock
            )
        );
    }

    // test that isUserInExperiment evaluates audience when audienceConditions is an audience leaf node.
    public function testIsUserInExperimentWithAudienceConditionsSetToAudienceIdString()
    {
        $config = new DatafileProjectConfig(DATAFILE, null, null);
        $experiment = $config->getExperimentFromKey('test_experiment');

        // Both audience Ids and audience conditions exist. Audience Ids is ignored.
        $experiment->setAudienceIds([]);
        $experiment->setAudienceConditions('7718080042');
        
        $this->assertTrue(
            Validator::isUserInExperiment(
                $config,
                $experiment,
                ['device_type' => 'iPhone', 'location' => 'San Francisco'],
                $this->loggerMock
            )
        );
    }

    public function testIsUserInExperimentWithUnknownAudienceId()
    {
        $config = new DatafileProjectConfig(DATAFILE, $this->loggerMock, new NoOpErrorHandler());
        $experiment = $config->getExperimentFromKey('test_experiment');

        // Both audience Ids and audience conditions exist. Audience Ids is ignored.
        $experiment->setAudienceIds([]);
        $experiment->setAudienceConditions(["or", "unknown_audience_id", "7718080042"]);
        
        // User qualifies for audience with ID "7718080042".
        $this->assertTrue(
            Validator::isUserInExperiment(
                $config,
                $experiment,
                ['device_type' => 'iPhone', 'location' => 'San Francisco'],
                $this->loggerMock
            )
        );
    }

    // test that isUserInExperiment evaluates simple audience.
    public function testIsUserInExperimentWithSimpleAudience()
    {
        $config = new DatafileProjectConfig(DATAFILE, null, null);
        $configMock = $this->getMockBuilder(DatafileProjectConfig::class)
            ->setConstructorArgs(array(DATAFILE, $this->loggerMock, new NoOpErrorHandler()))
            ->setMethods(array('getAudience'))
            ->getMock();

        $experiment = $configMock->getExperimentFromKey('test_experiment');
        $experiment->setAudienceIds(['11155', '7718080042']);
        $experiment->setAudienceConditions(null);

        $configMock->expects($this->at(0))
            ->method('getAudience')
            ->with('11155')
            ->will($this->returnValue($config->getAudience('11155')));

        $configMock->expects($this->at(1))
            ->method('getAudience')
            ->with('7718080042')
            ->will($this->returnValue($config->getAudience('7718080042')));

        $this->assertTrue(
            Validator::isUserInExperiment(
                $configMock,
                $experiment,
                ['device_type' => 'iPhone', 'location' => 'San Francisco'],
                $this->loggerMock
            )
        );
    }

    // test that isUserInExperiment evaluates complex audience.
    public function testIsUserInExperimentWithComplexAudience()
    {
        $config = new DatafileProjectConfig(DATAFILE_WITH_TYPED_AUDIENCES, null, null);
        $configMock = $this->getMockBuilder(DatafileProjectConfig::class)
            ->setConstructorArgs(array(DATAFILE_WITH_TYPED_AUDIENCES, $this->loggerMock, new NoOpErrorHandler()))
            ->setMethods(array('getAudience'))
            ->getMock();

        $experiment = $configMock->getExperimentFromKey('audience_combinations_experiment');
        $experiment->setAudienceIds([]);
        $experiment->setAudienceConditions(["or", ["and", "3468206642", "3988293898"], ["or", "3988293899",
                                 "3468206646", "3468206647", "3468206644", "3468206643"]]);

        // Qualifies for audience "3468206643".
        // Fails for audience "3468206642" hence "3988293898" is not evaluated.

        $configMock->expects($this->at(0))
            ->method('getAudience')
            ->with('3468206642')
            ->will($this->returnValue($config->getAudience('3468206642')));

        $configMock->expects($this->at(1))
            ->method('getAudience')
            ->with('3988293899')
            ->will($this->returnValue($config->getAudience('3988293899')));

        $configMock->expects($this->at(2))
            ->method('getAudience')
            ->with('3468206646')
            ->will($this->returnValue($config->getAudience('3468206646')));

        $configMock->expects($this->at(3))
            ->method('getAudience')
            ->with('3468206647')
            ->will($this->returnValue($config->getAudience('3468206647')));

        $configMock->expects($this->at(4))
            ->method('getAudience')
            ->with('3468206644')
            ->will($this->returnValue($config->getAudience('3468206644')));

        $configMock->expects($this->at(5))
            ->method('getAudience')
            ->with('3468206643')
            ->will($this->returnValue($config->getAudience('3468206643')));

        $this->assertTrue(
            Validator::isUserInExperiment(
                $configMock,
                $experiment,
                ['should_do_it' => true, 'house' => 'foo'],
                $this->loggerMock
            )
        );
    }

    public function testIsFeatureFlagValid()
    {
        $config = new DatafileProjectConfig(DATAFILE, new NoOpLogger(), new NoOpErrorHandler());
        $featureFlagSource = $config->getFeatureFlagFromKey('mutex_group_feature');

        // should return true when no experiment ids exist
        $featureFlag = clone $featureFlagSource;
        $featureFlag->setExperimentIds([]);
        $this->assertTrue(Validator::isFeatureFlagValid($config, $featureFlag));

        // should return true when only one experiment id exists
        $featureFlag = clone $featureFlagSource;
        $featureFlag->setExperimentIds(['122241']);
        $this->assertTrue(Validator::isFeatureFlagValid($config, $featureFlag));

        // should return true when more than one experiment ids exist that belong to the same group
        $featureFlag = clone $featureFlagSource;
        $this->assertTrue(Validator::isFeatureFlagValid($config, $featureFlag));

        //should return false when more than one experiment ids exist that belong to different group
        $featureFlag = clone $featureFlagSource;
        $experimentIds = $featureFlag->getExperimentIds();
        $experimentIds [] = '122241';
        $featureFlag->setExperimentIds($experimentIds);

        $this->assertFalse(Validator::isFeatureFlagValid($config, $featureFlag));
    }

    public function testDoesArrayContainOnlyStringKeys()
    {
        // Valid values
        $this->assertTrue(Validator::doesArrayContainOnlyStringKeys(
            ["name"=> "favorite_ice_cream", "type"=> "custom_attribute", "match"=> "exists"]
        ));

        // Invalid values
        $this->assertFalse(Validator::doesArrayContainOnlyStringKeys([]));
        $this->assertFalse(Validator::doesArrayContainOnlyStringKeys((object)[]));
        $this->assertFalse(Validator::doesArrayContainOnlyStringKeys(
            ["and", ["or", ["or", ["name"=> "favorite_ice_cream", "type"=> "custom_attribute","match"=> "exists"]]]]
        ));
        $this->assertFalse(Validator::doesArrayContainOnlyStringKeys(['hello' => 'world', 0 => 'bye']));
        $this->assertFalse(Validator::doesArrayContainOnlyStringKeys(['hello' => 'world', '0' => 'bye']));
        $this->assertFalse(Validator::doesArrayContainOnlyStringKeys(['hello' => 'world', 'and']));
        $this->assertFalse(Validator::doesArrayContainOnlyStringKeys('helloworld'));
        $this->assertFalse(Validator::doesArrayContainOnlyStringKeys(12));
        $this->assertFalse(Validator::doesArrayContainOnlyStringKeys('12.5'));
        $this->assertFalse(Validator::doesArrayContainOnlyStringKeys(true));
    }
}
