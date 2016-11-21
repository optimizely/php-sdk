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
include('TestData.php');

use Optimizely\Entity\Attribute;
use Optimizely\Entity\Audience;
use Optimizely\Entity\Event;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\Group;
use Optimizely\Entity\Variation;
use Optimizely\ProjectConfig;

class ProjectConfigTest extends \PHPUnit_Framework_TestCase
{
    private $config;

    protected function setUp()
    {
        $this->config = new ProjectConfig(DATAFILE);
    }

    public function testInit()
    {
        // Check version
        $version = new \ReflectionProperty(ProjectConfig::class, '_version');
        $version->setAccessible(true);
        $this->assertEquals('2', $version->getValue($this->config));

        // Check account ID
        $accountId = new \ReflectionProperty(ProjectConfig::class, '_accountId');
        $accountId->setAccessible(true);
        $this->assertEquals('1592310167', $accountId->getValue($this->config));

        // Check project ID
        $projectId = new \ReflectionProperty(ProjectConfig::class, '_projectId');
        $projectId->setAccessible(true);
        $this->assertEquals('7720880029', $projectId->getValue($this->config));

        // Check revision
        $revision = new \ReflectionProperty(ProjectConfig::class, '_revision');
        $revision->setAccessible(true);
        $this->assertEquals('15', $revision->getValue($this->config));

        // Check group ID map
        $groupIdMap = new \ReflectionProperty(ProjectConfig::class, '_groupIdMap');
        $groupIdMap->setAccessible(true);
        $this->assertEquals([
            '7722400015' => $this->config->getGroup('7722400015')
        ], $groupIdMap->getValue($this->config));

        // Check experiment key map
        $experimentKeyMap = new \ReflectionProperty(ProjectConfig::class, '_experimentKeyMap');
        $experimentKeyMap->setAccessible(true);
        $this->assertEquals([
            'test_experiment' => $this->config->getExperimentFromKey('test_experiment'),
            'group_experiment_1' => $this->config->getExperimentFromKey('group_experiment_1'),
            'group_experiment_2' => $this->config->getExperimentFromKey('group_experiment_2')
        ], $experimentKeyMap->getValue($this->config));

        // Check experiment ID map
        $experimentIdMap = new \ReflectionProperty(ProjectConfig::class, '_experimentIdMap');
        $experimentIdMap->setAccessible(true);
        $this->assertEquals([
            '7716830082' => $this->config->getExperimentFromId('7716830082'),
            '7723330021' => $this->config->getExperimentFromId('7723330021'),
            '7718750065' => $this->config->getExperimentFromId('7718750065')
        ], $experimentIdMap->getValue($this->config));

        // Check event key map
        $eventKeyMap = new \ReflectionProperty(ProjectConfig::class, '_eventKeyMap');
        $eventKeyMap->setAccessible(true);
        $this->assertEquals([
            'purchase' => $this->config->getEvent('purchase')
        ], $eventKeyMap->getValue($this->config));

        // Check attribute key map
        $attributeKeyMap = new \ReflectionProperty(ProjectConfig::class, '_attributeKeyMap');
        $attributeKeyMap->setAccessible(true);
        $this->assertEquals([
            'device_type' => $this->config->getAttribute('device_type'),
            'location' => $this->config->getAttribute('location')
        ], $attributeKeyMap->getValue($this->config));

        // Check audience ID map
        $audienceIdMap = new \ReflectionProperty(ProjectConfig::class, '_audienceIdMap');
        $audienceIdMap->setAccessible(true);
        $this->assertEquals([
            '7718080042' => $this->config->getAudience('7718080042')
        ], $audienceIdMap->getValue($this->config));

        // Check variation key map
        $variationKeyMap = new \ReflectionProperty(ProjectConfig::class, '_variationKeyMap');
        $variationKeyMap->setAccessible(true);
        $this->assertEquals([
            'test_experiment' => [
                'control' => $this->config->getVariationFromKey('test_experiment', 'control'),
                'variation' => $this->config->getVariationFromKey('test_experiment', 'variation')
            ],
            'group_experiment_1' => [
                'group_exp_1_var_1' => $this->config->getVariationFromKey('group_experiment_1', 'group_exp_1_var_1'),
                'group_exp_1_var_2' => $this->config->getVariationFromKey('group_experiment_1', 'group_exp_1_var_2')
            ],
            'group_experiment_2' => [
                'group_exp_2_var_1' => $this->config->getVariationFromKey('group_experiment_2', 'group_exp_2_var_1'),
                'group_exp_2_var_2' => $this->config->getVariationFromKey('group_experiment_2', 'group_exp_2_var_2')
            ]
        ], $variationKeyMap->getValue($this->config));

        // Check variation ID map
        $variationIdMap = new \ReflectionProperty(ProjectConfig::class, '_variationIdMap');
        $variationIdMap->setAccessible(true);
        $this->assertEquals([
            'test_experiment' => [
                '7722370027' => $this->config->getVariationFromId('test_experiment', '7722370027'),
                '7721010009' => $this->config->getVariationFromId('test_experiment', '7721010009')
            ],
            'group_experiment_1' => [
                '7722260071' => $this->config->getVariationFromId('group_experiment_1', '7722260071'),
                '7722360022' => $this->config->getVariationFromId('group_experiment_1', '7722360022')
            ],
            'group_experiment_2' => [
                '7713030086' => $this->config->getVariationFromId('group_experiment_2', '7713030086'),
                '7725250007' => $this->config->getVariationFromId('group_experiment_2', '7725250007')
            ]
        ], $variationIdMap->getValue($this->config));
    }

    public function testGetAccountId()
    {
        $this->assertEquals('1592310167', $this->config->getAccountId());
    }

    public function testGetProjectId()
    {
        $this->assertEquals('7720880029', $this->config->getProjectId());
    }

    public function testGetGroupValidId()
    {
        $group = $this->config->getGroup('7722400015');
        $this->assertEquals('7722400015', $group->getId());
        $this->assertEquals('random', $group->getPolicy());
    }

    public function testGetGroupInvalidId()
    {
        $this->assertEquals(new Group(), $this->config->getGroup('invalid_id'));
    }

    public function testGetExperimentValidKey()
    {
        $experiment = $this->config->getExperimentFromKey('test_experiment');
        $this->assertEquals('test_experiment', $experiment->getKey());
        $this->assertEquals('7716830082', $experiment->getId());
    }

    public function testGetExperimentInvalidKey()
    {
        $this->assertEquals(new Experiment(), $this->config->getExperimentFromKey('invalid_key'));
    }

    public function testGetExperimentValidId()
    {
        $experiment = $this->config->getExperimentFromId('7716830082');
        $this->assertEquals('7716830082', $experiment->getId());
        $this->assertEquals('test_experiment', $experiment->getKey());
    }

    public function testGetExperimentInvalidId()
    {
        $this->assertEquals(new Experiment(), $this->config->getExperimentFromId('42'));
    }

    public function testGetEventValidKey()
    {
        $event = $this->config->getEvent('purchase');
        $this->assertEquals('purchase', $event->getKey());
        $this->assertEquals('7718020063', $event->getId());
        $this->assertEquals(['7716830082', '7723330021', '7718750065'], $event->getExperimentIds());
    }

    public function testGetEventInvalidKey()
    {
        $this->assertEquals(new Event(), $this->config->getEvent('invalid_key'));
    }

    public function testGetAudienceValidId()
    {
        $audience = $this->config->getAudience('7718080042');
        $this->assertEquals('7718080042', $audience->getId());
        $this->assertEquals('iPhone users in San Francisco', $audience->getName());
    }

    public function testGetAudienceInvalidKey()
    {
        $this->assertEquals(new Audience(), $this->config->getAudience('invalid_id'));
    }

    public function testGetAttributeValidKey()
    {
        $attribute = $this->config->getAttribute('device_type');
        $this->assertEquals('device_type', $attribute->getKey());
        $this->assertEquals('7723280020', $attribute->getId());
    }

    public function testGetAttributeInvalidKey()
    {
        $this->assertEquals(new Attribute(), $this->config->getAttribute('invalid_key'));
    }

    public function testGetVariationFromKeyValidExperimentKeyValidVariationKey()
    {
        $variation = $this->config->getVariationFromKey('test_experiment', 'control');
        $this->assertEquals('7722370027', $variation->getId());
        $this->assertEquals('control', $variation->getKey());
    }

    public function testGetVariationFromKeyValidExperimentKeyInvalidVariationKey()
    {
        $this->assertEquals(new Variation(), $this->config->getVariationFromKey('test_experiment', 'invalid_key'));
    }

    public function testGetVariationFromKeyInvalidExperimentKey()
    {
        $this->assertEquals(new Variation(), $this->config->getVariationFromKey('invalid_experiment', '7722370027'));
    }

    public function testGetVariationFromIdValidExperimentKeyValidVariationId()
    {
        $variation = $this->config->getVariationFromId('test_experiment', '7722370027');
        $this->assertEquals('control', $variation->getKey());
        $this->assertEquals('7722370027', $variation->getId());
    }

    public function testGetVariationFromIdValidExperimentKeyInvalidVariationId()
    {
        $this->assertEquals(new Variation(), $this->config->getVariationFromId('test_experiment', 'invalid_id'));
    }

    public function testGetVariationFromIdInvalidExperimentKey()
    {
        $this->assertEquals(new Variation(), $this->config->getVariationFromId('invalid_experiment', '7722370027'));
    }
}
