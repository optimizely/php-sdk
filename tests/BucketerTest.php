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

use Optimizely\Bucketer;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\Variation;
use Optimizely\ProjectConfig;

class BucketerTest extends \PHPUnit_Framework_TestCase
{
    private $testUserId;
    private $config;

    public function setUp()
    {
        $this->testUserId = 'testUserId';
        $this->config = new ProjectConfig(DATAFILE);
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
            $generateBucketValueMethod->invoke(new Bucketer(), $this->getBucketingId('ppid1', '1886780721'))
        );
        $this->assertEquals(
            4299,
            $generateBucketValueMethod->invoke(new Bucketer(), $this->getBucketingId('ppid2', '1886780721'))
        );
        $this->assertEquals(
            2434,
            $generateBucketValueMethod->invoke(new Bucketer(), $this->getBucketingId('ppid2', '1886780722'))
        );
        $this->assertEquals(
            5439,
            $generateBucketValueMethod->invoke(new Bucketer(), $this->getBucketingId('ppid3', '1886780721'))
        );
        $this->assertEquals(
            6128,
            $generateBucketValueMethod->invoke(
                new Bucketer(),
                $this->getBucketingId(
                    'a very very very very very very very very very very very very very very very long ppd string',
                    '1886780721'
                )
            )
        );
    }

    public function testBucketValidExperimentNotInGroup()
    {
        $bucketer = new TestBucketer();
        $bucketer->setBucketValues([3000, 7000, 9000]);

        // control
        $this->assertEquals(
            new Variation('7722370027', 'control'),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('test_experiment'),
                $this->testUserId
            )
        );

        // variation
        $this->assertEquals(
            new Variation('7721010009', 'variation'),
            $bucketer->bucket(
                $this->config,
                $this->config->getExperimentFromKey('test_experiment'),
                $this->testUserId
            )
        );

        // No variation
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
        $bucketer = new TestBucketer();

        // group_experiment_1 (20% experiment)
        // variation 1
        $bucketer->setBucketValues([1000, 4000]);
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
        $bucketer = new Bucketer();

        $this->assertEquals(
            new Variation(),
            $bucketer->bucket($this->config, new Experiment(), $this->testUserId)
        );
    }

    public function testBucketValidExperimentNotInGroupUserInForcedVariation()
    {
        $bucketer = new Bucketer();

        $this->assertEquals(
            new Variation('7722370027', 'control'),
            $bucketer->bucket($this->config, $this->config->getExperimentFromKey('test_experiment'), 'user1')
        );
    }

    public function testBucketValidExperimentInGroupUserInForcedVariation()
    {
        $bucketer = new Bucketer();

        $this->assertEquals(
            new Variation('7722260071', 'group_exp_1_var_1'),
            $bucketer->bucket($this->config, $this->config->getExperimentFromKey('group_experiment_1'), 'user1')
        );
    }
}
