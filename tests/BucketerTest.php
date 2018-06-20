<?php
/**
 * Copyright 2016-2018, Optimizely
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
use Optimizely\Entity\Rollout;
use Optimizely\Entity\Variation;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Logger\DefaultLogger;
use Optimizely\Logger\NoOpLogger;
use Optimizely\ProjectConfig;

class BucketerTest extends \PHPUnit_Framework_TestCase
{
    private $testBucketingIdControl;
    private $testBucketingIdVariation;
    private $testBucketingIdGroupExp2Var2;
    private $testUserId;
    private $testUserIdBucketsToVariation;
    private $testUserIdBucketsToNoGroup;
    private $config;
    private $loggerMock;

    public function setUp()
    {
        $this->testBucketingIdControl = 'testBucketingIdControl!';  // generates bucketing number 3741
        $this->testBucketingIdVariation = '123456789'; // generates bucketing number 4567
        $this->testBucketingIdGroupExp2Var2 = '123456789'; // group_exp_2_var_2
        $this->testUserId = 'testUserId';
        $this->testUserIdBucketsToVariation = 'bucketsToVariation!';
        $this->testUserIdBucketsToNoGroup = 'testUserId';
        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();
        $this->config = new ProjectConfig(DATAFILE, $this->loggerMock, new NoOpErrorHandler());
    }

    private function getBucketingKey($bucketingId, $experimentId)
    {
        return $bucketingId.$experimentId;
    }

    public function testGenerateBucketValue()
    {
        $generateBucketValueMethod = new \ReflectionMethod(Bucketer::class, 'generateBucketValue');
        $generateBucketValueMethod->setAccessible(true);

        $this->assertSame(
            5254,
            $generateBucketValueMethod->invoke(new Bucketer($this->loggerMock), $this->getBucketingKey('ppid1', '1886780721'))
        );
        $this->assertSame(
            4299,
            $generateBucketValueMethod->invoke(new Bucketer($this->loggerMock), $this->getBucketingKey('ppid2', '1886780721'))
        );
        $this->assertSame(
            2434,
            $generateBucketValueMethod->invoke(new Bucketer($this->loggerMock), $this->getBucketingKey('ppid2', '1886780722'))
        );
        $this->assertSame(
            5439,
            $generateBucketValueMethod->invoke(new Bucketer($this->loggerMock), $this->getBucketingKey('ppid3', '1886780721'))
        );
        $this->assertSame(
            6128,
            $generateBucketValueMethod->invoke(
                new Bucketer($this->loggerMock),
                $this->getBucketingKey(
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
            ->with(Logger::DEBUG, sprintf('Assigned bucket 1000 to user "%s" with bucketing ID "%s".', $this->testUserId, $this->testBucketingIdControl));
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is in no variation.');

        $this->assertNull(
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('test_experiment'),
                $this->testBucketingIdControl,
                $this->testUserId
            )
        );

        // control
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Assigned bucket 3000 to user "%s" with bucketing ID "%s".', $this->testUserId, $this->testBucketingIdControl));
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(
                Logger::INFO,
                'User "testUserId" is in variation control of experiment test_experiment.'
            );

        $this->assertEquals(
            new Variation('7722370027', 'control'),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('test_experiment'),
                $this->testBucketingIdControl,
                $this->testUserId
            )
        );

        // variation
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Assigned bucket 7000 to user "%s" with bucketing ID "%s".', $this->testUserId, $this->testBucketingIdControl));
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(
                Logger::INFO,
                'User "testUserId" is in variation variation of experiment test_experiment.'
            );

        $this->assertEquals(
            new Variation('7721010009', 'variation'),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('test_experiment'),
                $this->testBucketingIdControl,
                $this->testUserId
            )
        );

        // No variation
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Assigned bucket 9000 to user "%s" with bucketing ID "%s".', $this->testUserId, $this->testBucketingIdControl));
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is in no variation.');

        $this->assertNull(
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('test_experiment'),
                $this->testBucketingIdControl,
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
            ->with(Logger::DEBUG, sprintf('Assigned bucket 1000 to user "%s" with bucketing ID "%s".', $this->testUserId, $this->testBucketingIdControl));
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at(2))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Assigned bucket 4000 to user "%s" with bucketing ID "%s".', $this->testUserId, $this->testBucketingIdControl));
        $this->loggerMock->expects($this->at(3))
            ->method('log')
            ->with(
                Logger::INFO,
                'User "testUserId" is in variation group_exp_1_var_1 of experiment group_experiment_1.'
            );

        $this->assertEquals(
            new Variation(
                '7722260071',
                'group_exp_1_var_1',
                true,
                [
                [
                  "id" => "155563",
                  "value" => "groupie_1_v1"
                ]
                ]
            ),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('group_experiment_1'),
                $this->testBucketingIdControl,
                $this->testUserId
            )
        );

        // variation 2
        $bucketer->setBucketValues([1500, 7000]);
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Assigned bucket 1500 to user "%s" with bucketing ID "%s".', $this->testUserId, $this->testBucketingIdControl));
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at(2))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Assigned bucket 7000 to user "%s" with bucketing ID "%s".', $this->testUserId, $this->testBucketingIdControl));
        $this->loggerMock->expects($this->at(3))
            ->method('log')
            ->with(
                Logger::INFO,
                'User "testUserId" is in variation group_exp_1_var_2 of experiment group_experiment_1.'
            );

        $this->assertEquals(
            new Variation(
                '7722360022',
                'group_exp_1_var_2',
                true,
                [
                [
                  "id" => "155563",
                  "value" => "groupie_1_v2"
                ]
                ]
            ),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('group_experiment_1'),
                $this->testBucketingIdControl,
                $this->testUserId
            )
        );

        // User not in experiment
        $bucketer->setBucketValues([5000, 7000]);
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Assigned bucket 5000 to user "%s" with bucketing ID "%s".', $this->testUserId, $this->testBucketingIdControl));
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is not in experiment group_experiment_1 of group 7722400015.');

        $this->assertNull(
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('group_experiment_1'),
                $this->testBucketingIdControl,
                $this->testUserId
            )
        );

        // User not in any experiment (previously allocated space)
        $bucketer->setBucketValues([400]);
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Assigned bucket 400 to user "%s" with bucketing ID "%s".', $this->testUserId, $this->testBucketingIdControl));
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is in no experiment.');

        $this->assertNull(
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('group_experiment_1'),
                $this->testBucketingIdControl,
                $this->testUserId
            )
        );

        // User not in any experiment (never allocated space)
        $bucketer->setBucketValues([9000]);
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Assigned bucket 9000 to user "%s" with bucketing ID "%s".', $this->testUserId, $this->testBucketingIdControl));
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" is in no experiment.');
        $this->assertNull(
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('group_experiment_1'),
                $this->testBucketingIdControl,
                $this->testUserId
            )
        );
    }

    public function testBucketInvalidExperiment()
    {
        $bucketer = new Bucketer($this->loggerMock);
        $this->loggerMock->expects($this->never())
            ->method('log');

        $this->assertNull(
            $bucketer->bucket($this->config, new Experiment(), $this->testBucketingIdControl, $this->testUserId)
        );
    }

    public function testBucketWithBucketingId()
    {
        $bucketer = new Bucketer($this->loggerMock);
        $experiment = $this->config->getExperimentFromKey('test_experiment');

        // make sure that the bucketing ID is used for the variation
        // bucketing and not the user ID
        $this->assertEquals(
            new Variation('7722370027', 'control'),
            $bucketer->bucket(
                $this->config,
                $experiment,
                $this->testBucketingIdControl,
                $this->testUserIdBucketsToVariation
            )
        );
    }

    // test for invalid experiment keys
    // null variation should be returned
    public function testBucketVariationInvalidExperimentsWithBucketingId()
    {
        $bucketer = new TestBucketer($this->loggerMock);
        $bucketer->setBucketValues([1000, 3000, 7000, 9000]);

        $this->assertNull(
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('invalid_experiment'),
                $this->testBucketingIdVariation,
                $this->testUserId
            )
        );
    }

    // make sure that the bucketing ID is used to bucket the user into a group
    // and not the user ID
    public function testBucketVariationGroupedExperimentsWithBucketingId()
    {
        $bucketer = new Bucketer($this->loggerMock);

        $this->assertEquals(
            new Variation(
                '7725250007',
                'group_exp_2_var_2',
                true,
                [
                [
                  "id" => "155563",
                  "value" => "groupie_2_v1"
                ]
                ]
            ),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('group_experiment_2'),
                $this->testBucketingIdGroupExp2Var2,
                $this->testUserIdBucketsToNoGroup
            )
        );
    }

    public function testBucketWithRolloutRule()
    {
        $bucketer = new TestBucketer($this->loggerMock);
        $bucketer->setBucketValues([4999, 5000]);

        $rollout = $this->config->getRolloutFromId('166660');
        $rollout_rule = null;
        $rollout_rules = $rollout->getExperiments();
        foreach ($rollout_rules as $rule) {
            if ($rule->getKey() == 'rollout_1_exp_3') {
                $rollout_rule = $rule;
            }
        }

        $expectedVariation = new Variation(
            '177778',
            '177778',
            true,
            [
                [
                  "id"=> "155556",
                  "value"=> "false"
                ]
              ]
        );

        $this->assertEquals(
            $expectedVariation,
            $bucketer->bucket(
                $this->config,
                $rollout_rule,
                'bucketingId',
                'userId'
            )
        );

        $this->assertNull(
            $bucketer->bucket(
                $this->config,
                $rollout_rule,
                'bucketingId',
                'userId'
            )
        );
    }
}
