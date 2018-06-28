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

use Monolog\Logger;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Logger\NoOpLogger;
use Optimizely\ProjectConfig;
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

        // Valid attributes
        $this->assertTrue(
            Validator::areAttributesValid(
                [
                'location' => 'San Francisco',
                'browser' => 'Firefox'
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

        // Mixed array as attributes
        $this->assertFalse(Validator::areAttributesValid([0, 1, 2, 42, 'abc' => 'def']));
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

    public function testIsUserInExperimentNoAudienceUsedInExperiment()
    {
        $config = new ProjectConfig(DATAFILE, new NoOpLogger(), new NoOpErrorHandler());
        $this->assertTrue(
            Validator::isUserInExperiment(
                $config,
                $config->getExperimentFromKey('paused_experiment'),
                []
            )
        );
    }

    public function testIsUserInExperimentAudienceUsedInExperimentNoAttributesProvided()
    {
        $config = new ProjectConfig(DATAFILE, new NoOpLogger(), new NoOpErrorHandler());

        // Test with empty attributes
        $this->assertFalse(
            Validator::isUserInExperiment(
                $config,
                $config->getExperimentFromKey('test_experiment'),
                []
            )
        );

        // Test with null attributes
        $this->assertFalse(
            Validator::isUserInExperiment(
                $config,
                $config->getExperimentFromKey('test_experiment'),
                null
            )
        );
    }

    public function testIsUserInExperimentAudienceMatch()
    {
        $config = new ProjectConfig(DATAFILE, new NoOpLogger(), new NoOpErrorHandler());
        $this->assertTrue(
            Validator::isUserInExperiment(
                $config,
                $config->getExperimentFromKey('test_experiment'),
                ['device_type' => 'iPhone', 'location' => 'San Francisco']
            )
        );
    }

    public function testIsUserInExperimentAudienceNoMatch()
    {
        $config = new ProjectConfig(DATAFILE, new NoOpLogger(), new NoOpErrorHandler());
        $this->assertFalse(
            Validator::isUserInExperiment(
                $config,
                $config->getExperimentFromKey('test_experiment'),
                ['device_type' => 'Android', 'location' => 'San Francisco']
            )
        );
    }

    public function testIsFeatureFlagValid()
    {
        $config = new ProjectConfig(DATAFILE, new NoOpLogger(), new NoOpErrorHandler());
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
}
