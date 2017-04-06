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
include('TestData.php');

use Monolog\Logger;
use Optimizely\Entity\Attribute;
use Optimizely\Entity\Audience;
use Optimizely\Entity\Event;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\Group;
use Optimizely\Entity\Variation;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Exceptions\InvalidAttributeException;
use Optimizely\Exceptions\InvalidAudienceException;
use Optimizely\Exceptions\InvalidEventException;
use Optimizely\Exceptions\InvalidExperimentException;
use Optimizely\Exceptions\InvalidGroupException;
use Optimizely\Exceptions\InvalidVariationException;
use Optimizely\Logger\NoOpLogger;
use Optimizely\ProjectConfig;

class ProjectConfigTest extends \PHPUnit_Framework_TestCase
{
    private $config;
    private $loggerMock;
    private $errorHandlerMock;

    protected function setUp()
    {
        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();
        // Mock Error handler
        $this->errorHandlerMock = $this->getMockBuilder(NoOpErrorHandler::class)
            ->setMethods(array('handleError'))
            ->getMock();

        $this->config = new ProjectConfig(DATAFILE, $this->loggerMock, $this->errorHandlerMock);
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
            'paused_experiment' => $this->config->getExperimentFromKey('paused_experiment'),
            'group_experiment_1' => $this->config->getExperimentFromKey('group_experiment_1'),
            'group_experiment_2' => $this->config->getExperimentFromKey('group_experiment_2'),
            'launched_experiment' => $this->config->getExperimentFromKey('launched_experiment')
        ], $experimentKeyMap->getValue($this->config));

        // Check experiment ID map
        $experimentIdMap = new \ReflectionProperty(ProjectConfig::class, '_experimentIdMap');
        $experimentIdMap->setAccessible(true);
        $this->assertEquals([
            '7716830082' => $this->config->getExperimentFromId('7716830082'),
            '7723330021' => $this->config->getExperimentFromId('7723330021'),
            '7718750065' => $this->config->getExperimentFromId('7718750065'),
            '7716830585' => $this->config->getExperimentFromId('7716830585'),
            '7716830586' => $this->config->getExperimentFromId('7716830586')
        ], $experimentIdMap->getValue($this->config));

        // Check event key map
        $eventKeyMap = new \ReflectionProperty(ProjectConfig::class, '_eventKeyMap');
        $eventKeyMap->setAccessible(true);
        $this->assertEquals([
            'purchase' => $this->config->getEvent('purchase'),
            'click' => $this->config->getEvent('click')
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
            'paused_experiment' => [
                'control' => $this->config->getVariationFromKey('paused_experiment', 'control'),
                'variation' => $this->config->getVariationFromKey('paused_experiment', 'variation')
            ],
            'group_experiment_1' => [
                'group_exp_1_var_1' => $this->config->getVariationFromKey('group_experiment_1', 'group_exp_1_var_1'),
                'group_exp_1_var_2' => $this->config->getVariationFromKey('group_experiment_1', 'group_exp_1_var_2')
            ],
            'group_experiment_2' => [
                'group_exp_2_var_1' => $this->config->getVariationFromKey('group_experiment_2', 'group_exp_2_var_1'),
                'group_exp_2_var_2' => $this->config->getVariationFromKey('group_experiment_2', 'group_exp_2_var_2')
            ],
            'launched_experiment' => [
                'control' => $this->config->getVariationFromKey('launched_experiment', 'control'),
                'variation' => $this->config->getVariationFromKey('launched_experiment', 'variation')
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
            'paused_experiment' => [
                '7722370427' => $this->config->getVariationFromId('paused_experiment', '7722370427'),
                '7721010509' => $this->config->getVariationFromId('paused_experiment', '7721010509')
            ],
            'group_experiment_1' => [
                '7722260071' => $this->config->getVariationFromId('group_experiment_1', '7722260071'),
                '7722360022' => $this->config->getVariationFromId('group_experiment_1', '7722360022')
            ],
            'group_experiment_2' => [
                '7713030086' => $this->config->getVariationFromId('group_experiment_2', '7713030086'),
                '7725250007' => $this->config->getVariationFromId('group_experiment_2', '7725250007')
            ],
            'launched_experiment' => [
                '7722370428' => $this->config->getVariationFromId('launched_experiment', '7722370428'),
                '7721010510' => $this->config->getVariationFromId('launched_experiment', '7721010510')
            ]
        ], $variationIdMap->getValue($this->config));
    }

    public function testInitWithDatafileV3()
    {
        // Init with v3 datafile
        $this->config = new ProjectConfig(DATAFILE_V3, $this->loggerMock, $this->errorHandlerMock);

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
            'paused_experiment' => $this->config->getExperimentFromKey('paused_experiment'),
            'group_experiment_1' => $this->config->getExperimentFromKey('group_experiment_1'),
            'group_experiment_2' => $this->config->getExperimentFromKey('group_experiment_2')
        ], $experimentKeyMap->getValue($this->config));

        // Check experiment ID map
        $experimentIdMap = new \ReflectionProperty(ProjectConfig::class, '_experimentIdMap');
        $experimentIdMap->setAccessible(true);
        $this->assertEquals([
            '7716830082' => $this->config->getExperimentFromId('7716830082'),
            '7723330021' => $this->config->getExperimentFromId('7723330021'),
            '7718750065' => $this->config->getExperimentFromId('7718750065'),
            '7716830585' => $this->config->getExperimentFromId('7716830585')
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
            'paused_experiment' => [
                'control' => $this->config->getVariationFromKey('paused_experiment', 'control'),
                'variation' => $this->config->getVariationFromKey('paused_experiment', 'variation')
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
            'paused_experiment' => [
                '7722370427' => $this->config->getVariationFromId('paused_experiment', '7722370427'),
                '7721010509' => $this->config->getVariationFromId('paused_experiment', '7721010509')
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

    public function testInitWithMoreData()
    {
        // Init with datafile consisting of more fields
        $this->config = new ProjectConfig(DATAFILE_MORE_DATA, $this->loggerMock, $this->errorHandlerMock);

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
            'paused_experiment' => $this->config->getExperimentFromKey('paused_experiment'),
            'group_experiment_1' => $this->config->getExperimentFromKey('group_experiment_1'),
            'group_experiment_2' => $this->config->getExperimentFromKey('group_experiment_2')
        ], $experimentKeyMap->getValue($this->config));

        // Check experiment ID map
        $experimentIdMap = new \ReflectionProperty(ProjectConfig::class, '_experimentIdMap');
        $experimentIdMap->setAccessible(true);
        $this->assertEquals([
            '7716830082' => $this->config->getExperimentFromId('7716830082'),
            '7723330021' => $this->config->getExperimentFromId('7723330021'),
            '7718750065' => $this->config->getExperimentFromId('7718750065'),
            '7716830585' => $this->config->getExperimentFromId('7716830585')
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
            'paused_experiment' => [
                'control' => $this->config->getVariationFromKey('paused_experiment', 'control'),
                'variation' => $this->config->getVariationFromKey('paused_experiment', 'variation')
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
            'paused_experiment' => [
                '7722370427' => $this->config->getVariationFromId('paused_experiment', '7722370427'),
                '7721010509' => $this->config->getVariationFromId('paused_experiment', '7721010509')
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

    public function testGetRevision()
    {
        $this->assertEquals('15', $this->config->getRevision());
    }

    public function testGetGroupValidId()
    {
        $group = $this->config->getGroup('7722400015');
        $this->assertEquals('7722400015', $group->getId());
        $this->assertEquals('random', $group->getPolicy());
    }

    public function testGetGroupInvalidId()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Group ID "invalid_id" is not in datafile.');
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidGroupException('Provided group is not in datafile.'));

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
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Experiment key "invalid_key" is not in datafile.');
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidExperimentException('Provided experiment is not in datafile.'));

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
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Experiment ID "42" is not in datafile.');
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidExperimentException('Provided experiment is not in datafile.'));

        $this->assertEquals(new Experiment(), $this->config->getExperimentFromId('42'));
    }

    public function testGetEventValidKey()
    {
        $event = $this->config->getEvent('purchase');
        $this->assertEquals('purchase', $event->getKey());
        $this->assertEquals('7718020063', $event->getId());
        $this->assertEquals(['7716830082', '7723330021', '7718750065', '7716830585'], $event->getExperimentIds());
    }

    public function testGetEventInvalidKey()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Event key "invalid_key" is not in datafile.');
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidEventException('Provided event is not in datafile.'));

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
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Audience ID "invalid_id" is not in datafile.');
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidAudienceException('Provided audience is not in datafile.'));

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
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Attribute key "invalid_key" is not in datafile.');
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidAttributeException('Provided attribute is not in datafile.'));

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
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(
                Logger::ERROR,
                'No variation key "invalid_key" defined in datafile for experiment "test_experiment".'
            );
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidVariationException('Provided variation is not in datafile.'));

        $this->assertEquals(new Variation(), $this->config->getVariationFromKey('test_experiment', 'invalid_key'));
    }

    public function testGetVariationFromKeyInvalidExperimentKey()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(
                Logger::ERROR,
                'No variation key "control" defined in datafile for experiment "invalid_experiment".'
            );
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidVariationException('Provided variation is not in datafile.'));

        $this->assertEquals(new Variation(), $this->config->getVariationFromKey('invalid_experiment', 'control'));
    }

    public function testGetVariationFromIdValidExperimentKeyValidVariationId()
    {
        $variation = $this->config->getVariationFromId('test_experiment', '7722370027');
        $this->assertEquals('control', $variation->getKey());
        $this->assertEquals('7722370027', $variation->getId());
    }

    public function testGetVariationFromIdValidExperimentKeyInvalidVariationId()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(
                Logger::ERROR,
                'No variation ID "invalid_id" defined in datafile for experiment "test_experiment".'
            );
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidVariationException('Provided variation is not in datafile.'));

        $this->assertEquals(new Variation(), $this->config->getVariationFromId('test_experiment', 'invalid_id'));
    }

    public function testGetVariationFromIdInvalidExperimentKey()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(
                Logger::ERROR,
                'No variation ID "7722370027" defined in datafile for experiment "invalid_experiment".'
            );
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidVariationException('Provided variation is not in datafile.'));

        $this->assertEquals(new Variation(), $this->config->getVariationFromId('invalid_experiment', '7722370027'));
    }
}
