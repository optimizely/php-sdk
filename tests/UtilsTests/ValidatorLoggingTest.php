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

 use Monolog\Logger;
 use Optimizely\ErrorHandler\NoOpErrorHandler;
 use Optimizely\Logger\NoOpLogger;
 use Optimizely\ProjectConfig;
 use Optimizely\Utils\Validator;

class ValidatorLoggingTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();

        $this->config = new ProjectConfig(DATAFILE, new NoOpLogger(), new NoOpErrorHandler());

        $this->typedConfig = new ProjectConfig(DATAFILE_WITH_TYPED_AUDIENCES, new NoOpLogger(), new NoOpErrorHandler());

        $this->collectedLogs = [];

        $this->collectLogsForAssertion = function ($a, $b) {
            $this->collectedLogs[] = array($a,$b);
        };
    }

    public function testIsUserInExperimentWithNoAudience()
    {
        $experiment = $this->config->getExperimentFromKey('test_experiment');
        $experiment->setAudienceIds([]);
        $experiment->setAudienceConditions([]);

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, "Evaluating audiences for experiment \"test_experiment\": [].");

        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, "Audiences for experiment \"test_experiment\" collectively evaluated to TRUE.");

        $this->assertTrue(Validator::isUserInExperiment($this->config, $experiment, [], $this->loggerMock));
    }

    public function testIsUserInExperimentEvaluatesAudienceIds()
    {
        $userAttributes = [
           "test_attribute" => "test_value_1"
        ];

        $experiment = $this->config->getExperimentFromKey('test_experiment');
        $experiment->setAudienceIds(['11155']);
        $experiment->setAudienceConditions(null);

        $this->loggerMock->expects($this->any())
                         ->method('log')
                         ->will($this->returnCallback($this->collectLogsForAssertion));

        Validator::isUserInExperiment($this->config, $experiment, $userAttributes, $this->loggerMock);

        $this->assertContains([Logger::DEBUG, "Evaluating audiences for experiment \"test_experiment\": [\"11155\"]."], $this->collectedLogs);
        $this->assertContains(
            [Logger::DEBUG, "Starting to evaluate audience \"11155\" with conditions: [\"and\",[\"or\",[\"or\",{\"name\":\"browser_type\",\"type\":\"custom_attribute\",\"value\":\"chrome\"}]]]."],
            $this->collectedLogs
        );
        $this->assertContains([Logger::INFO, "Audience \"11155\" evaluated to UNKNOWN."], $this->collectedLogs);
        $this->assertContains([Logger::INFO, "Audiences for experiment \"test_experiment\" collectively evaluated to FALSE."], $this->collectedLogs);
    }

    public function testIsUserInExperimenEvaluatesAudienceConditions()
    {
        $experiment = $this->typedConfig->getExperimentFromKey('audience_combinations_experiment');
        $experiment->setAudienceIds([]);
        $experiment->setAudienceConditions(['or', ['or', '3468206642', '3988293898']]);

        $this->loggerMock->expects($this->any())
                        ->method('log')
                        ->will($this->returnCallback($this->collectLogsForAssertion));

        Validator::isUserInExperiment($this->typedConfig, $experiment, ["house" => "I am in Slytherin"], $this->loggerMock);

        $this->assertContains(
            [Logger::DEBUG, "Evaluating audiences for experiment \"audience_combinations_experiment\": [\"or\",[\"or\",\"3468206642\",\"3988293898\"]]."],
            $this->collectedLogs
        );
        $this->assertContains(
            [Logger::DEBUG, "Starting to evaluate audience \"3468206642\" with conditions: [\"and\",[\"or\",[\"or\",{\"name\":\"house\",\"type\":\"custom_attribute\",\"value\":\"Gryffindor\"}]]]."],
            $this->collectedLogs
        );
        $this->assertContains(
            [Logger::INFO, "Audience \"3468206642\" evaluated to FALSE."],
            $this->collectedLogs
        );
        $this->assertContains(
            [Logger::DEBUG, "Starting to evaluate audience \"3988293898\" with conditions: [\"and\",[\"or\",[\"or\",{\"name\":\"house\",\"type\":\"custom_attribute\",\"match\":\"substring\",\"value\":\"Slytherin\"}]]]."],
            $this->collectedLogs
        );
        $this->assertContains(
            [Logger::INFO, "Audience \"3988293898\" evaluated to TRUE."],
            $this->collectedLogs
        );
        $this->assertContains([Logger::INFO, "Audiences for experiment \"audience_combinations_experiment\" collectively evaluated to TRUE."], $this->collectedLogs);
    }
}
