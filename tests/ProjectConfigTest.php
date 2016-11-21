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
        $this->markTestSkipped('To be implemented.');
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
