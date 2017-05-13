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

use Monolog\Logger;
use Optimizely\Bucketer;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\Variation;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Logger\NoOpLogger;
use Optimizely\ProjectConfig;

class BucketerTest extends \PHPUnit_Framework_TestCase
{
    private $testUserId;
    private $config;
    private $loggerMock;

    public function setUp()
    {
        $this->testUserId = 'testUserId';
        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();
        $this->config = new ProjectConfig(DATAFILE, $this->loggerMock, new NoOpErrorHandler());
    }

    private function getBucketingId($userId, $experimentId)
    {
        return $userId.$experimentId;
    }

    public function testGenerateBucketValue()
    {
        $generateBucketValueMethod = new \ReflectionMethod(Bucketer::class, 'generateBucketValue');
        $generateBucketValueMethod->setAccessible(true);

        $this->assertEquals(
            5254,
            $generateBucketValueMethod->invoke(new Bucketer($this->loggerMock), $this->getBucketingId('ppid1', '1886780721'))
        );
        $this->assertEquals(
            4299,
            $generateBucketValueMethod->invoke(new Bucketer($this->loggerMock), $this->getBucketingId('ppid2', '1886780721'))
        );
        $this->assertEquals(
            2434,
            $generateBucketValueMethod->invoke(new Bucketer($this->loggerMock), $this->getBucketingId('ppid2', '1886780722'))
        );
        $this->assertEquals(
            5439,
            $generateBucketValueMethod->invoke(new Bucketer($this->loggerMock), $this->getBucketingId('ppid3', '1886780721'))
        );
        $this->assertEquals(
            6128,
            $generateBucketValueMethod->invoke(
                new Bucketer($this->loggerMock),
                $this->getBucketingId(
                    'a very very very very very very very very very very very very very very very long ppd string',
                    '1886780721'
                )
            )
        );
    }

    public function testBucketValidExperimentNotInGroup()
    {
        $bucketer = new TestBucketer($this->loggerMock);
        $bucketer->setBucketValues([1000, 3000, 7000, 9000]);
        // Total calls in this test
        $this->loggerMock->expects($this->exactly(8))
            ->method('log');

        // No variation (empty entity ID)
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 1000 to user "testUserId".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is in no variation.');

        $this->assertEquals(
            new Variation(),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('test_experiment'),
                $this->testUserId
            )
        );

        // control
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 3000 to user "testUserId".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO,
                'User "testUserId" is in variation control of experiment test_experiment.');

        $this->assertEquals(
            new Variation('7722370027', 'control'),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('test_experiment'),
                $this->testUserId
            )
        );

        // variation
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 7000 to user "testUserId".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO,
                'User "testUserId" is in variation variation of experiment test_experiment.');

        $this->assertEquals(
            new Variation('7721010009', 'variation'),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('test_experiment'),
                $this->testUserId
            )
        );

        // No variation
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 9000 to user "testUserId".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is in no variation.');

        $this->assertEquals(
            new Variation(),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('test_experiment'),
                $this->testUserId
            )
        );
    }

    public function testBucketValidExperimentInGroup()
    {
        $bucketer = new TestBucketer($this->loggerMock);
        // Total calls in this test
        $this->loggerMock->expects($this->exactly(14))
            ->method('log');

        // group_experiment_1 (15% experiment)
        // variation 1
        $bucketer->setBucketValues([1000, 4000]);
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 1000 to user "testUserId".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at(2))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 4000 to user "testUserId".');
        $this->loggerMock->expects($this->at(3))
            ->method('log')
            ->with(Logger::INFO,
                'User "testUserId" is in variation group_exp_1_var_1 of experiment group_experiment_1.');

        $this->assertEquals(
            new Variation('7722260071', 'group_exp_1_var_1'),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('group_experiment_1'),
                $this->testUserId
            )
        );

        // variation 2
        $bucketer->setBucketValues([1500, 7000]);
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 1500 to user "testUserId".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at(2))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 7000 to user "testUserId".');
        $this->loggerMock->expects($this->at(3))
            ->method('log')
            ->with(Logger::INFO,
                'User "testUserId" is in variation group_exp_1_var_2 of experiment group_experiment_1.');

        $this->assertEquals(
            new Variation('7722360022', 'group_exp_1_var_2'),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('group_experiment_1'),
                $this->testUserId
            )
        );

        // User not in experiment
        $bucketer->setBucketValues([5000, 7000]);
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 5000 to user "testUserId".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is not in experiment group_experiment_1 of group 7722400015.');

        $this->assertEquals(
            new Variation(),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('group_experiment_1'),
                $this->testUserId
            )
        );

        // User not in any experiment (previously allocated space)
        $bucketer->setBucketValues([400]);
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 400 to user "testUserId".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is in no experiment.');

        $this->assertEquals(
            new Variation(),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('group_experiment_1'),
                $this->testUserId
            )
        );

        // User not in any experiment (never allocated space)
        $bucketer->setBucketValues([9000]);
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 9000 to user "testUserId".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is in no experiment.');
        $this->assertEquals(
            new Variation(),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('group_experiment_1'),
                $this->testUserId
            )
        );
    }

    public function testBucketInvalidExperiment()
    {
        $bucketer = new Bucketer($this->loggerMock);
        $this->loggerMock->expects($this->never())
            ->method('log');

        $this->assertEquals(
            new Variation(),
            $bucketer->bucket($this->config, new Experiment(), $this->testUserId)
        );
    }

    public function testGetForcedVariationExperimentNotInGroupUserInForcedVariation()
    {
        $bucketer = new Bucketer($this->loggerMock);
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::INFO, 'User "user1" is forced in variation "control" of experiment "test_experiment".');

        $this->assertEquals(
            new Variation('7722370027', 'control'),
            $bucketer->getForcedVariation($this->config, $this->config->getExperimentFromKey('test_experiment'), 'user1')
        );
    }

    public function testGetForcedVariationExperimentInGroupUserInForcedVariation()
    {
        $bucketer = new Bucketer($this->loggerMock);
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::INFO, 'User "user1" is forced in variation "group_exp_1_var_1" of experiment "group_experiment_1".');

        $this->assertEquals(
            new Variation('7722260071', 'group_exp_1_var_1'),
            $bucketer->getForcedVariation($this->config, $this->config->getExperimentFromKey('group_experiment_1'), 'user1')
        );
    }
}
