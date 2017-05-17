<?php
/**
 * Copyright 2017, Optimizely
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

use Exception;
use Monolog\Logger;
use Optimizely\Bucketer;
use Optimizely\DecisionService\DecisionService;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\Variation;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Logger\NoOpLogger;
use Optimizely\ProjectConfig;


class DecisionServiceTest extends \PHPUnit_Framework_TestCase
{
    private $bucketerMock;
    private $config;
    private $decisionService;
    private $loggerMock;
    private $testUserId;

    public function setUp()
    {
        $this->testUserId = 'testUserId';
        $this->testUserAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();
        $this->config = new ProjectConfig(DATAFILE, $this->loggerMock, new NoOpErrorHandler());

        // Mock bucketer
        $this->bucketerMock = $this->getMockBuilder(Bucketer::class)
            ->setConstructorArgs(array($this->loggerMock))
            ->setMethods(array('bucket'))
            ->getMock();
    }

    public function testGetVariationReturnsNullWhenExperimentIsNotRunning()
    {
        $this->bucketerMock->expects($this->never())
            ->method('bucket');

        $pausedExperiment = $this->config->getExperimentFromKey('paused_experiment');

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($pausedExperiment, $this->testUserId);

        $this->assertNull($variation);
    }

    public function testGetVariationBucketsUserWhenExperimentIsRunning()
    {
        $expectedVariation = new Variation('7722370027', 'control');
        $this->bucketerMock->expects($this->once())
            ->method('bucket')
            ->willReturn($expectedVariation);

        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, $this->testUserId, $this->testUserAttributes);

        $this->assertEquals(
            $expectedVariation,
            $variation
        );
    }

    public function testGetVariationReturnsWhitelistedVariation()
    {
        $expectedVariation = new Variation('7722370027', 'control');
        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');

        $this->bucketerMock->expects($this->never())
            ->method('bucket');
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, 'User "user1" is forced in variation "control" of experiment "test_experiment".');

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, 'user1');

        $this->assertEquals(
            $expectedVariation,
            $variation
        );
    }

    public function testGetVariationReturnsWhitelistedVariationForGroupedExperiment()
    {
        $expectedVariation = new Variation('7722260071', 'group_exp_1_var_1');
        $runningExperiment = $this->config->getExperimentFromKey('group_experiment_1');

        $this->bucketerMock->expects($this->never())
            ->method('bucket');
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, 'User "user1" is forced in variation "group_exp_1_var_1" of experiment "group_experiment_1".');

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, 'user1');

        $this->assertEquals(
            $expectedVariation,
            $variation
        );
    }

    public function testGetVariationBucketsWhenForcedVariationsIsEmpty()
    {
        $expectedVariation = new Variation('7722370027', 'control');
        $this->bucketerMock->expects($this->once())
            ->method('bucket')
            ->willReturn($expectedVariation);

        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');

        // empty out the forcedVariations property
        $experiment = new \ReflectionProperty(Experiment::class, '_forcedVariations');
        $experiment->setAccessible(true);
        $experiment->setValue($runningExperiment, array());

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, 'user1', $this->testUserAttributes);

        $this->assertEquals(
            $expectedVariation,
            $variation
        );
    }

    public function testGetVariationBucketsWhenWhitelistedVariationIsInvalid()
    {
        $expectedVariation = new Variation('7722370027', 'control');
        $this->bucketerMock->expects($this->once())
            ->method('bucket')
            ->willReturn($expectedVariation);

        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');

        // modify the forcedVariation to point to invalid variation
        $experiment = new \ReflectionProperty(Experiment::class, '_forcedVariations');
        $experiment->setAccessible(true);
        $experiment->setValue($runningExperiment, [
            'user_1' => 'invalid'
        ]);

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, 'user1', $this->testUserAttributes);

        $this->assertEquals(
            $expectedVariation,
            $variation
        );
    }

    public function testGetVariationBucketsUserWhenUserIsNotWhitelisted()
    {
        $expectedVariation = new Variation('7722370027', 'control');
        $this->bucketerMock->expects($this->once())
            ->method('bucket')
            ->willReturn($expectedVariation);

        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, 'not_whitelisted_user', $this->testUserAttributes);

        $this->assertEquals(
            $expectedVariation,
            $variation
        );
    }

    public function testGetVariationReturnsNullIfUserDoesNotMeetAudienceConditions()
    {
        $this->bucketerMock->expects($this->never())
            ->method('bucket');

        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, $this->testUserId); // no matching attributes

        $this->assertNull($variation);
    }
}
