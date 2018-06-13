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

require 'TestData.php';

use Monolog\Logger;
use Optimizely\Entity\Attribute;
use Optimizely\Entity\Audience;
use Optimizely\Entity\Event;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\FeatureFlag;
use Optimizely\Entity\Group;
use Optimizely\Entity\Rollout;
use Optimizely\Entity\Variation;
use Optimizely\Entity\VariableUsage;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Exceptions\InvalidAttributeException;
use Optimizely\Exceptions\InvalidAudienceException;
use Optimizely\Exceptions\InvalidEventException;
use Optimizely\Exceptions\InvalidExperimentException;
use Optimizely\Exceptions\InvalidFeatureFlagException;
use Optimizely\Exceptions\InvalidRolloutException;
use Optimizely\Exceptions\InvalidGroupException;
use Optimizely\Exceptions\InvalidVariationException;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Optimizely;
use Optimizely\ProjectConfig;
use Optimizely\Utils\ConfigParser;

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
        $this->assertEquals('4', $version->getValue($this->config));

        // Check account ID
        $accountId = new \ReflectionProperty(ProjectConfig::class, '_accountId');
        $accountId->setAccessible(true);
        $this->assertEquals('1592310167', $accountId->getValue($this->config));

        // Check project ID
        $projectId = new \ReflectionProperty(ProjectConfig::class, '_projectId');
        $projectId->setAccessible(true);
        $this->assertEquals('7720880029', $projectId->getValue($this->config));

        // Check botFiltering
        $botFiltering = new \ReflectionProperty(ProjectConfig::class, '_botFiltering');
        $botFiltering->setAccessible(true);
        $this->assertSame(true, $botFiltering->getValue($this->config));

        // Check revision
        $revision = new \ReflectionProperty(ProjectConfig::class, '_revision');
        $revision->setAccessible(true);
        $this->assertEquals('15', $revision->getValue($this->config));

        // Check group ID map
        $groupIdMap = new \ReflectionProperty(ProjectConfig::class, '_groupIdMap');
        $groupIdMap->setAccessible(true);
        $this->assertEquals(
            [
            '7722400015' => $this->config->getGroup('7722400015')
            ],
            $groupIdMap->getValue($this->config)
        );

        // Check experiment key map
        $experimentKeyMap = new \ReflectionProperty(ProjectConfig::class, '_experimentKeyMap');
        $experimentKeyMap->setAccessible(true);
        $this->assertEquals(
            [
            'test_experiment' => $this->config->getExperimentFromKey('test_experiment'),
            'paused_experiment' => $this->config->getExperimentFromKey('paused_experiment'),
            'group_experiment_1' => $this->config->getExperimentFromKey('group_experiment_1'),
            'group_experiment_2' => $this->config->getExperimentFromKey('group_experiment_2'),
            'test_experiment_multivariate' => $this->config->getExperimentFromKey('test_experiment_multivariate'),
            'test_experiment_with_feature_rollout' => $this->config->getExperimentFromKey('test_experiment_with_feature_rollout'),
            'test_experiment_double_feature' =>  $this->config->getExperimentFromKey('test_experiment_double_feature'),
            'test_experiment_integer_feature' =>  $this->config->getExperimentFromKey('test_experiment_integer_feature')
            ],
            $experimentKeyMap->getValue($this->config)
        );

        // Check experiment ID map
        $experimentIdMap = new \ReflectionProperty(ProjectConfig::class, '_experimentIdMap');
        $experimentIdMap->setAccessible(true);
        $this->assertEquals(
            [
            '7716830082' => $this->config->getExperimentFromId('7716830082'),
            '7723330021' => $this->config->getExperimentFromId('7723330021'),
            '7718750065' => $this->config->getExperimentFromId('7718750065'),
            '7716830585' => $this->config->getExperimentFromId('7716830585'),
            '122230' => $this->config->getExperimentFromId('122230'),
            '122235' => $this->config->getExperimentFromId('122235'),
            '122238' => $this->config->getExperimentFromId('122238'),
            '122241' => $this->config->getExperimentFromId('122241')
            ],
            $experimentIdMap->getValue($this->config)
        );

        // Check event key map
        $eventKeyMap = new \ReflectionProperty(ProjectConfig::class, '_eventKeyMap');
        $eventKeyMap->setAccessible(true);
        $this->assertEquals(
            [
            'purchase' => $this->config->getEvent('purchase'),
            'unlinked_event' => $this->config->getEvent('unlinked_event')
            ],
            $eventKeyMap->getValue($this->config)
        );

        // Check attribute key map
        $attributeKeyMap = new \ReflectionProperty(ProjectConfig::class, '_attributeKeyMap');
        $attributeKeyMap->setAccessible(true);
        $this->assertEquals(
            [
            'device_type' => $this->config->getAttribute('device_type'),
            'location' => $this->config->getAttribute('location'),
            '$opt_xyz' => $this->config->getAttribute('$opt_xyz')
            ],
            $attributeKeyMap->getValue($this->config)
        );

        // Check audience ID map
        $audienceIdMap = new \ReflectionProperty(ProjectConfig::class, '_audienceIdMap');
        $audienceIdMap->setAccessible(true);
        $this->assertEquals(
            [
            '7718080042' => $this->config->getAudience('7718080042'),
            '11155' => $this->config->getAudience('11155')
            ],
            $audienceIdMap->getValue($this->config)
        );

        // Check variation key map
        $variationKeyMap = new \ReflectionProperty(ProjectConfig::class, '_variationKeyMap');
        $variationKeyMap->setAccessible(true);

        $this->assertEquals(
            [
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
            'test_experiment_multivariate' => [
                'Fred' => $this->config->getVariationFromKey('test_experiment_multivariate', 'Fred'),
                'Feorge' => $this->config->getVariationFromKey('test_experiment_multivariate', 'Feorge'),
                'Gred' => $this->config->getVariationFromKey('test_experiment_multivariate', 'Gred'),
                'George' => $this->config->getVariationFromKey('test_experiment_multivariate', 'George')
            ],
            'test_experiment_with_feature_rollout' => [
                'control' => $this->config->getVariationFromKey('test_experiment_with_feature_rollout', 'control'),
                'variation' => $this->config->getVariationFromKey('test_experiment_with_feature_rollout', 'variation')
            ],
            'test_experiment_double_feature' => [
                'control' => $this->config->getVariationFromKey('test_experiment_double_feature', 'control'),
                'variation' => $this->config->getVariationFromKey('test_experiment_double_feature', 'variation')
            ],
            'test_experiment_integer_feature' => [
                'control' => $this->config->getVariationFromKey('test_experiment_integer_feature', 'control'),
                'variation' => $this->config->getVariationFromKey('test_experiment_integer_feature', 'variation')
            ],
            'rollout_1_exp_1' => [
                '177771' => $this->config->getVariationFromKey('rollout_1_exp_1', '177771')
            ],
            'rollout_1_exp_2' => [
                '177773' => $this->config->getVariationFromKey('rollout_1_exp_2', '177773')
            ],
            'rollout_1_exp_3' => [
                '177778' => $this->config->getVariationFromKey('rollout_1_exp_3', '177778')
            ],
            'rollout_2_exp_1' => [
                '177775' => $this->config->getVariationFromKey('rollout_2_exp_1', '177775')
            ],
            'rollout_2_exp_2' => [
                '177780' => $this->config->getVariationFromKey('rollout_2_exp_2', '177780')
            ]
            ],
            $variationKeyMap->getValue($this->config)
        );

        // Check variation ID map
        $variationIdMap = new \ReflectionProperty(ProjectConfig::class, '_variationIdMap');
        $variationIdMap->setAccessible(true);
        $this->assertEquals(
            [
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
            'test_experiment_multivariate' => [
                '122231' => $this->config->getVariationFromId('test_experiment_multivariate', '122231'),
                '122232' => $this->config->getVariationFromId('test_experiment_multivariate', '122232'),
                '122233' => $this->config->getVariationFromId('test_experiment_multivariate', '122233'),
                '122234' => $this->config->getVariationFromId('test_experiment_multivariate', '122234')
            ],
            'test_experiment_with_feature_rollout' => [
                '122236' => $this->config->getVariationFromId('test_experiment_with_feature_rollout', '122236'),
                '122237' => $this->config->getVariationFromId('test_experiment_with_feature_rollout', '122237')
            ],
            'test_experiment_double_feature' => [
                '122239' => $this->config->getVariationFromId('test_experiment_double_feature', '122239'),
                '122240' => $this->config->getVariationFromId('test_experiment_double_feature', '122240')
            ],
            'test_experiment_integer_feature' => [
                '122242' => $this->config->getVariationFromId('test_experiment_integer_feature', '122242'),
                '122243' => $this->config->getVariationFromId('test_experiment_integer_feature', '122243')
            ],
            'rollout_1_exp_1' => [
                '177771' => $this->config->getVariationFromId('rollout_1_exp_1', '177771')
            ],
            'rollout_1_exp_2' => [
                '177773' => $this->config->getVariationFromId('rollout_1_exp_2', '177773')
            ],
            'rollout_1_exp_3' => [
                '177778' => $this->config->getVariationFromId('rollout_1_exp_3', '177778')
            ],
            'rollout_2_exp_1' => [
                '177775' => $this->config->getVariationFromId('rollout_2_exp_1', '177775')
            ],
            'rollout_2_exp_2' => [
                '177780' => $this->config->getVariationFromId('rollout_2_exp_2', '177780')
            ]
            ],
            $variationIdMap->getValue($this->config)
        );


        // Check feature flag key map
        $featureFlagKeyMap = new \ReflectionProperty(ProjectConfig::class, '_featureKeyMap');
        $featureFlagKeyMap->setAccessible(true);
        $this->assertEquals(
            [
            'boolean_feature' => $this->config->getFeatureFlagFromKey('boolean_feature'),
            'double_single_variable_feature' => $this->config->getFeatureFlagFromKey('double_single_variable_feature'),
            'integer_single_variable_feature' => $this->config->getFeatureFlagFromKey('integer_single_variable_feature'),
            'boolean_single_variable_feature' => $this->config->getFeatureFlagFromKey('boolean_single_variable_feature'),
            'string_single_variable_feature' => $this->config->getFeatureFlagFromKey('string_single_variable_feature'),
            'multi_variate_feature' => $this->config->getFeatureFlagFromKey('multi_variate_feature'),
            'mutex_group_feature' => $this->config->getFeatureFlagFromKey('mutex_group_feature'),
            'empty_feature' => $this->config->getFeatureFlagFromKey('empty_feature')
            ],
            $featureFlagKeyMap->getValue($this->config)
        );


        // Check rollout id map
        $rolloutIdMap = new \ReflectionProperty(ProjectConfig::class, '_rolloutIdMap');
        $rolloutIdMap->setAccessible(true);
        $this->assertEquals(
            [
            '166660' => $this->config->getRolloutFromId('166660'),
            '166661' => $this->config->getRolloutFromId('166661')
            ],
            $rolloutIdMap->getValue($this->config)
        );


        // Check variation entity
        $variableUsages = [
            new VariableUsage("155560", "F"),
            new VariableUsage("155561", "red")
        ];
        $expectedVariation = new Variation("122231", "Fred", true, $variableUsages);
        $actualVariation = $this->config->getVariationFromKey("test_experiment_multivariate", "Fred");

        $this->assertEquals($expectedVariation, $actualVariation);
    }

    public function testVariationParsingWithoutFeatureEnabledProp()
    {
        $variables = [
                [
                  "id"=> "155556",
                  "value"=> "true"
                ]
              ];

        $data = [
            [
              "id"=> "177771",
              "key"=> "my_var",
              "variables"=> $variables
            ]
          ];

        $variationIdMap = ConfigParser::generateMap($data, 'id', Variation::class);

        $variation = $variationIdMap["177771"];
        $this->assertEquals("177771", $variation->getId());
        $this->assertEquals("my_var", $variation->getKey());

        $variableUsageMap = ConfigParser::generateMap($variables, null, VariableUsage::class);
        $this->assertEquals($variableUsageMap, $variation->getVariables());

        // assert featureEnabled by default is set to false when property not provided in data file
        $this->assertFalse($variation->getFeatureEnabled());
    }

    public function testGetAccountId()
    {
        $this->assertEquals('1592310167', $this->config->getAccountId());
    }

    public function testGetProjectId()
    {
        $this->assertEquals('7720880029', $this->config->getProjectId());
    }

    public function testGetBotFiltering()
    {
        $botFiltering = new \ReflectionProperty(ProjectConfig::class, '_botFiltering');
        $botFiltering->setAccessible(true);
        $this->assertSame($botFiltering->getValue($this->config), $this->config->getBotFiltering());
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

    public function testGetFeatureFlagInvalidKey()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'FeatureFlag Key "42" is not in datafile.');
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidFeatureFlagException('Provided feature flag is not in datafile.'));

        $this->assertEquals(new FeatureFlag(), $this->config->getFeatureFlagFromKey('42'));
    }

    public function testGetRolloutInvalidId()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Rollout with ID "42" is not in the datafile.');

        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidRolloutException('Provided rollout is not in datafile.'));

        $this->assertEquals(new Rollout(), $this->config->getRolloutFromId('42'));
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

    public function testGetAttributeValidKeyWithReservedPrefix()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::WARNING, 
                'Attribute $opt_xyz unexpectedly has reserved prefix $opt_; using attribute ID instead of reserved attribute name.'
            );

        $validAttrWithReservedPrefix = new Attribute('7723340006', '$opt_xyz');
        $this->assertEquals($validAttrWithReservedPrefix, $this->config->getAttribute('$opt_xyz'));
    }

    public function testGetAttributeInvalidKey()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Attribute key "invalid_key" is not in datafile.');
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidAttributeException('Provided attribute is not in datafile.'));

        $this->assertNull($this->config->getAttribute('invalid_key'));
    }

    public function testGetAttributeMissingKeyWithReservedPrefix()
    {
        $validAttrWithReservedPrefix = new Attribute('$opt_007', '$opt_007');
        $this->assertEquals($validAttrWithReservedPrefix, $this->config->getAttribute('$opt_007'));
    }

    public function testGetAttributeForControlAttributes()
    {
        # Should return attribute with ID same as given Key
        $optUserAgentAttr = new Attribute('$opt_user_agent', '$opt_user_agent');
        $this->assertEquals($optUserAgentAttr, $this->config->getAttribute('$opt_user_agent'));

        $optBucketingIdAttr = new Attribute('$opt_bucketing_id', '$opt_bucketing_id');
        $this->assertEquals($optBucketingIdAttr, $this->config->getAttribute('$opt_bucketing_id'));
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

    public function testIsVariationIdValid()
    {
        $this->assertTrue($this->config->isVariationIdValid('test_experiment', '7722370027'));
        $this->assertFalse($this->config->isVariationIdValid('test_experiment', 'invalid'));
    }

    // test set/get forced variation for the following cases:
    //      - valid and invalid user ID
    //      - valid and invalid experiment key
    //      - valid and invalid variation key, null variation key
    public function testSetGetForcedVariation()
    {
        $userId = 'test_user';
        $invalidUserId = 'invalid_user';
        $experimentKey = 'test_experiment';
        $experimentKey2 = 'group_experiment_1';
        $invalidExperimentKey = 'invalid_experiment';
        $variationKey = 'control';
        $variationKey2 = 'group_exp_1_var_1';
        $invalidVariationKey = 'invalid_variation';
        
        $optlyObject = new Optimizely(DATAFILE, new ValidEventDispatcher(), $this->loggerMock);
        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'San Francisco'
        ];

        $optlyObject->activate('test_experiment', 'test_user', $userAttributes);

        $this->config = new ProjectConfig(DATAFILE, $this->loggerMock, $this->errorHandlerMock);

        // invalid experiment key should return a null variation
        $this->assertFalse($this->config->setForcedVariation($invalidExperimentKey, $userId, $variationKey));
        $this->assertNull($this->config->getForcedVariation($invalidExperimentKey, $userId));

        // setting a null variation should return a null variation
        $this->assertTrue($this->config->setForcedVariation($experimentKey, $userId, null));
        $this->assertNull($this->config->getForcedVariation($experimentKey, $userId));

        // setting an invalid variation should return a null variation
        $this->assertFalse($this->config->setForcedVariation($experimentKey, $userId, $invalidVariationKey));
        $this->assertNull($this->config->getForcedVariation($experimentKey, $userId));

        // confirm the forced variation is returned after a set
        $this->assertTrue($this->config->setForcedVariation($experimentKey, $userId, $variationKey));
        $forcedVariation = $this->config->getForcedVariation($experimentKey, $userId);
        $this->assertEquals($variationKey, $forcedVariation->getKey());

        // check multiple sets
        $this->assertTrue($this->config->setForcedVariation($experimentKey2, $userId, $variationKey2));
        $forcedVariation2 = $this->config->getForcedVariation($experimentKey2, $userId);
        $this->assertEquals($variationKey2, $forcedVariation2->getKey());
        // make sure the second set does not overwrite the first set
        $forcedVariation = $this->config->getForcedVariation($experimentKey, $userId);
        $this->assertEquals($variationKey, $forcedVariation->getKey());
        // make sure unsetting the second experiment-to-variation mapping does not unset the
        // first experiment-to-variation mapping
        $this->assertTrue($this->config->setForcedVariation($experimentKey2, $userId, null));
        $forcedVariation = $this->config->getForcedVariation($experimentKey, $userId);
        $this->assertEquals($variationKey, $forcedVariation->getKey());

        // an invalid user ID should return a null variation
        $this->assertNull($this->config->getForcedVariation($experimentKey, $invalidUserId));
    }

    // test that all the logs in setForcedVariation are getting called
    public function testSetForcedVariationLogs()
    {
        $userId = 'test_user';
        $experimentKey = 'test_experiment';
        $experimentId = '7716830082';
        $invalidExperimentKey = 'invalid_experiment';
        $variationKey = 'control';
        $variationId = '7722370027';
        $invalidVariationKey = 'invalid_variation';
        $callIndex = 0;

        $this->loggerMock->expects($this->exactly(4))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::ERROR, sprintf('Experiment key "%s" is not in datafile.', $invalidExperimentKey));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Variation mapped to experiment "%s" has been removed for user "%s".', $experimentKey, $userId));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::ERROR, sprintf('No variation key "%s" defined in datafile for experiment "%s".', $invalidVariationKey, $experimentKey));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Set variation "%s" for experiment "%s" and user "%s" in the forced variation map.', $variationId, $experimentId, $userId));

        $this->config = new ProjectConfig(DATAFILE, $this->loggerMock, $this->errorHandlerMock);

        $this->config->setForcedVariation($invalidExperimentKey, $userId, $variationKey);
        $this->config->setForcedVariation($experimentKey, $userId, null);
        $this->config->setForcedVariation($experimentKey, $userId, $invalidVariationKey);
        $this->config->setForcedVariation($experimentKey, $userId, $variationKey);
    }

    // test that all the logs in getForcedVariation are getting called
    public function testGetForcedVariationLogs()
    {
        $userId = 'test_user';
        $invalidUserId = 'invalid_user';
        $experimentKey = 'test_experiment';
        $experimentId = '7716830082';
        $invalidExperimentKey = 'invalid_experiment';
        $pausedExperimentKey = 'paused_experiment';
        $variationKey = 'control';
        $variationId = '7722370027';
        $callIndex = 0;

        $this->loggerMock->expects($this->exactly(5))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Set variation "%s" for experiment "%s" and user "%s" in the forced variation map.', $variationId, $experimentId, $userId));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('User "%s" is not in the forced variation map.', $invalidUserId));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::ERROR, sprintf('Experiment key "%s" is not in datafile.', $invalidExperimentKey));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('No experiment "%s" mapped to user "%s" in the forced variation map.', $pausedExperimentKey, $userId));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Variation "%s" is mapped to experiment "%s" and user "%s" in the forced variation map', $variationKey, $experimentKey, $userId));

        $this->config = new ProjectConfig(DATAFILE, $this->loggerMock, $this->errorHandlerMock);

        $this->config->setForcedVariation($experimentKey, $userId, $variationKey);
        $this->config->getForcedVariation($experimentKey, $invalidUserId);
        $this->config->getForcedVariation($invalidExperimentKey, $userId);
        $this->config->getForcedVariation($pausedExperimentKey, $userId);
        $this->config->getForcedVariation($experimentKey, $userId);
    }
}
