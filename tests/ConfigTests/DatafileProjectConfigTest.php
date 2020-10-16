<?php
/**
 * Copyright 2016-2020, Optimizely
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

namespace Optimizely\Config\Tests;

require(dirname(__FILE__).'/../TestData.php');

use Monolog\Logger;
use Optimizely\Config\DatafileProjectConfig;
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
use Optimizely\Exceptions\InvalidDatafileVersionException;
use Optimizely\Exceptions\InvalidEventException;
use Optimizely\Exceptions\InvalidExperimentException;
use Optimizely\Exceptions\InvalidFeatureFlagException;
use Optimizely\Exceptions\InvalidRolloutException;
use Optimizely\Exceptions\InvalidGroupException;
use Optimizely\Exceptions\InvalidVariationException;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Optimizely;
use Optimizely\Tests\ValidEventDispatcher;
use Optimizely\Utils\ConfigParser;

class DatafileProjectConfigTest extends \PHPUnit_Framework_TestCase
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

        $this->config = new DatafileProjectConfig(DATAFILE, $this->loggerMock, $this->errorHandlerMock);
    }

    public function testInit()
    {
        // Check version
        $version = new \ReflectionProperty(DatafileProjectConfig::class, '_version');
        $version->setAccessible(true);
        $this->assertEquals('4', $version->getValue($this->config));

        // Check account ID
        $accountId = new \ReflectionProperty(DatafileProjectConfig::class, '_accountId');
        $accountId->setAccessible(true);
        $this->assertEquals('1592310167', $accountId->getValue($this->config));

        // Check project ID
        $projectId = new \ReflectionProperty(DatafileProjectConfig::class, '_projectId');
        $projectId->setAccessible(true);
        $this->assertEquals('7720880029', $projectId->getValue($this->config));

        // Check botFiltering
        $botFiltering = new \ReflectionProperty(DatafileProjectConfig::class, '_botFiltering');
        $botFiltering->setAccessible(true);
        $this->assertSame(true, $botFiltering->getValue($this->config));

        // Check revision
        $revision = new \ReflectionProperty(DatafileProjectConfig::class, '_revision');
        $revision->setAccessible(true);
        $this->assertEquals('15', $revision->getValue($this->config));

        // Check group ID map
        $groupIdMap = new \ReflectionProperty(DatafileProjectConfig::class, '_groupIdMap');
        $groupIdMap->setAccessible(true);
        $this->assertEquals(
            [
            '7722400015' => $this->config->getGroup('7722400015')
            ],
            $groupIdMap->getValue($this->config)
        );

        // Check experiment key map
        $experimentKeyMap = new \ReflectionProperty(DatafileProjectConfig::class, '_experimentKeyMap');
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
            'test_experiment_integer_feature' =>  $this->config->getExperimentFromKey('test_experiment_integer_feature'),
            'test_experiment_2' =>  $this->config->getExperimentFromKey('test_experiment_2'),
            'test_experiment_json_feature' =>  $this->config->getExperimentFromKey('test_experiment_json_feature'),
            'rollout_1_exp_1' =>  $this->config->getExperimentFromKey('rollout_1_exp_1'),
            'rollout_1_exp_2' =>  $this->config->getExperimentFromKey('rollout_1_exp_2'),
            'rollout_1_exp_3' =>  $this->config->getExperimentFromKey('rollout_1_exp_3'),
            'rollout_2_exp_1' =>  $this->config->getExperimentFromKey('rollout_2_exp_1'),
            'rollout_2_exp_2' =>  $this->config->getExperimentFromKey('rollout_2_exp_2'),
            ],
            $experimentKeyMap->getValue($this->config)
        );

        // Check experiment ID map
        $experimentIdMap = new \ReflectionProperty(DatafileProjectConfig::class, '_experimentIdMap');
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
            '122241' => $this->config->getExperimentFromId('122241'),
            '111133' => $this->config->getExperimentFromId('111133'),
            '122245' => $this->config->getExperimentFromId('122245'),
            '177770' => $this->config->getExperimentFromId('177770'),
            '177772' => $this->config->getExperimentFromId('177772'),
            '177776' => $this->config->getExperimentFromId('177776'),
            '177774' => $this->config->getExperimentFromId('177774'),
            '177779' => $this->config->getExperimentFromId('177779'),
            ],
            $experimentIdMap->getValue($this->config)
        );

        // Check event key map
        $eventKeyMap = new \ReflectionProperty(DatafileProjectConfig::class, '_eventKeyMap');
        $eventKeyMap->setAccessible(true);
        $this->assertEquals(
            [
            'purchase' => $this->config->getEvent('purchase'),
            'unlinked_event' => $this->config->getEvent('unlinked_event'),
            'multi_exp_event' => $this->config->getEvent('multi_exp_event')
            ],
            $eventKeyMap->getValue($this->config)
        );

        // Check attribute key map
        $attributeKeyMap = new \ReflectionProperty(DatafileProjectConfig::class, '_attributeKeyMap');
        $attributeKeyMap->setAccessible(true);
        $this->assertEquals(
            [
            'device_type' => $this->config->getAttribute('device_type'),
            'location' => $this->config->getAttribute('location'),
            '$opt_xyz' => $this->config->getAttribute('$opt_xyz'),
            'boolean_key' => $this->config->getAttribute('boolean_key'),
            'double_key' => $this->config->getAttribute('double_key'),
            'integer_key' => $this->config->getAttribute('integer_key')
            ],
            $attributeKeyMap->getValue($this->config)
        );

        // Check audience ID map
        $audienceIdMap = new \ReflectionProperty(DatafileProjectConfig::class, '_audienceIdMap');
        $audienceIdMap->setAccessible(true);
        $this->assertEquals(
            [
            '7718080042' => $this->config->getAudience('7718080042'),
            '11155' => $this->config->getAudience('11155')
            ],
            $audienceIdMap->getValue($this->config)
        );

        // Check variation key map
        $variationKeyMap = new \ReflectionProperty(DatafileProjectConfig::class, '_variationKeyMap');
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
            ],
            'test_experiment_2' => [
                'test_variation_1' => $this->config->getVariationFromKey('test_experiment_2', 'test_variation_1'),
                'test_variation_2' => $this->config->getVariationFromKey('test_experiment_2', 'test_variation_2')
            ],
            'test_experiment_json_feature' => [
                'json_variation' => $this->config->getVariationFromKey('test_experiment_json_feature', 'json_variation')
            ]
            ],
            $variationKeyMap->getValue($this->config)
        );

        // Check variation ID map
        $variationIdMap = new \ReflectionProperty(DatafileProjectConfig::class, '_variationIdMap');
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
            ],
            'test_experiment_2' => [
                '151239' => $this->config->getVariationFromId('test_experiment_2', '151239'),
                '151240' => $this->config->getVariationFromId('test_experiment_2', '151240')
            ],
            'test_experiment_json_feature' => [
                '122246' => $this->config->getVariationFromId('test_experiment_json_feature', '122246')
            ]
            ],
            $variationIdMap->getValue($this->config)
        );


        // Check feature flag key map
        $featureFlagKeyMap = new \ReflectionProperty(DatafileProjectConfig::class, '_featureKeyMap');
        $featureFlagKeyMap->setAccessible(true);
        $this->assertEquals(
            [
            'boolean_feature' => $this->config->getFeatureFlagFromKey('boolean_feature'),
            'double_single_variable_feature' => $this->config->getFeatureFlagFromKey('double_single_variable_feature'),
            'integer_single_variable_feature' => $this->config->getFeatureFlagFromKey('integer_single_variable_feature'),
            'boolean_single_variable_feature' => $this->config->getFeatureFlagFromKey('boolean_single_variable_feature'),
            'string_single_variable_feature' => $this->config->getFeatureFlagFromKey('string_single_variable_feature'),
            'multiple_variables_feature' => $this->config->getFeatureFlagFromKey('multiple_variables_feature'),
            'multi_variate_feature' => $this->config->getFeatureFlagFromKey('multi_variate_feature'),
            'mutex_group_feature' => $this->config->getFeatureFlagFromKey('mutex_group_feature'),
            'empty_feature' => $this->config->getFeatureFlagFromKey('empty_feature')
            ],
            $featureFlagKeyMap->getValue($this->config)
        );


        // Check rollout id map
        $rolloutIdMap = new \ReflectionProperty(DatafileProjectConfig::class, '_rolloutIdMap');
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

        // Check Experiment Feature Map
        $experimentFeatureMap = new \ReflectionProperty(DatafileProjectConfig::class, '_experimentFeatureMap');
        $experimentFeatureMap->setAccessible(true);
        $this->assertEquals(
            [
                '111133' => ['155549'],
                '122238' => ['155550'],
                '122241' => ['155552'],
                '122235' => ['155557'],
                '122230' => ['155559'],
                '7723330021' => ['155562'],
                '7718750065' => ['155562'],
                '122245' => ['155597']
            ],
            $experimentFeatureMap->getValue($this->config)
        );
    }

    public function testExceptionThrownForUnsupportedVersion()
    {
        // Verify that an exception is thrown when given datafile version is unsupported //
        $this->setExpectedException(
            InvalidDatafileVersionException::class,
            'This version of the PHP SDK does not support the given datafile version: 5.'
        );

        $this->config = new DatafileProjectConfig(
            UNSUPPORTED_DATAFILE,
            $this->loggerMock,
            $this->errorHandlerMock
        );
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
        $this->assertNull($variation->getFeatureEnabled());
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
        $botFiltering = new \ReflectionProperty(DatafileProjectConfig::class, '_botFiltering');
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

    public function testGetAudiencePrefersTypedAudiencesOverAudiences()
    {
        $projectConfig = new DatafileProjectConfig(
            DATAFILE_WITH_TYPED_AUDIENCES,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        // test that typedAudience is returned when an audience exists with the same ID.
        $audience = $projectConfig->getAudience('3988293898');

        $this->assertEquals('3988293898', $audience->getId());
        $this->assertEquals('substringString', $audience->getName());

        $expectedConditions = json_decode('["and", ["or", ["or", {"name": "house", "type": "custom_attribute",
                         "match": "substring", "value": "Slytherin"}]]]', true);
        $this->assertEquals($expectedConditions, $audience->getConditions());
        $this->assertEquals($expectedConditions, $audience->getConditionsList());

        // test that normal audience is returned if no typedAudience exists with the same ID.
        $audience = $projectConfig->getAudience('3468206642');

        $this->assertEquals('3468206642', $audience->getId());
        $this->assertEquals('exactString', $audience->getName());

        $expectedConditions = '["and", ["or", ["or", {"name": "house", "type": "custom_attribute", "value": "Gryffindor"}]]]';
        $this->assertEquals($expectedConditions, $audience->getConditions());
        $expectedConditionsList = json_decode($expectedConditions, true);
        $this->assertEquals($expectedConditionsList, $audience->getConditionsList());
    }

    public function testGetAudienceInvalidKey()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Audience ID "invalid_id" is not in datafile.');
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidAudienceException('Provided audience is not in datafile.'));

        $this->assertNull($this->config->getAudience('invalid_id'));
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
            ->with(
                Logger::WARNING,
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
    
    // Test that a true is returned if experiment is a feature test, false otherwise.
    public function testIsFeatureExperiment()
    {
        $experiment = $this->config->getExperimentFromKey('test_experiment');
        $featureExperiment = $this->config->getExperimentFromKey('test_experiment_double_feature');

        $this->assertTrue($this->config->isFeatureExperiment($featureExperiment->getId()));
        $this->assertFalse($this->config->isFeatureExperiment($experiment->getId()));
    }

    public function testToDatafile()
    {
        $expectedDatafile = DATAFILE_FOR_OPTIMIZELY_CONFIG;
        $this->config = new DatafileProjectConfig($expectedDatafile, $this->loggerMock, $this->errorHandlerMock);
        $actualDatafile = $this->config->toDatafile();

        $this->assertEquals($expectedDatafile, $actualDatafile);
    }
}
