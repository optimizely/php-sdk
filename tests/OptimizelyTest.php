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

use Exception;
use Monolog\Logger;
use Optimizely\ErrorHandler\DefaultErrorHandler;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Event\Builder\EventBuilder;
use Optimizely\Event\LogEvent;
use Optimizely\Exceptions\InvalidAttributeException;
use Optimizely\Logger\DefaultLogger;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Optimizely;
use Optimizely\ProjectConfig;
use TypeError;


class OptimizelyTest extends \PHPUnit_Framework_TestCase
{
    private $datafile;
    private $eventBuilderMock;
    private $loggerMock;
    private $optimizelyObject;
    private $projectConfig;

    public function setUp()
    {
        $this->datafile = DATAFILE;
        $this->testBucketingIdControl = 'testBucketingIdControl!';  // generates bucketing number 3741
        $this->testBucketingIdVariation = '123456789'; // generates bucketing number 4567
        $this->variationKeyControl = 'control';
        $this->variationKeyVariation = 'variation';
        $this->userId = 'test_user';
        $this->experimentKey = 'test_experiment';

        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();
        $this->optimizelyObject = new Optimizely($this->datafile, null, $this->loggerMock);

        $this->projectConfig = new ProjectConfig($this->datafile, $this->loggerMock, new NoOpErrorHandler());

        // Mock EventBuilder
        $this->eventBuilderMock = $this->getMockBuilder(EventBuilder::class)
            ->setMethods(array('createImpressionEvent', 'createConversionEvent'))
            ->getMock();
    }

    public function testInitValidEventDispatcher()
    {
        $validDispatcher = new ValidEventDispatcher();
        $optlyObject = new Optimizely($this->datafile, $validDispatcher);
    }

    public function testInitInvalidEventDispatcher()
    {
        $invalidDispatcher = new InvalidEventDispatcher();
        try {
            $optlyObject = new Optimizely($this->datafile, $invalidDispatcher);
        } catch (Exception $exception) {
            return;
        } catch (TypeError $exception) {
            return;
        }

        $this->fail('Unexpected behavior. Invalid event dispatcher went through.');
    }

    public function testInitValidLogger()
    {
        $validLogger = new DefaultLogger();
        $optlyObject = new Optimizely($this->datafile, null, $validLogger);
    }

    public function testInitInvalidLogger()
    {
        $invalidLogger = new InvalidLogger();
        try {
            $optlyObject = new Optimizely($this->datafile, null, $invalidLogger);
        } catch (Exception $exception) {
            return;
        } catch (TypeError $exception) {
            return;
        }

        $this->fail('Unexpected behavior. Invalid logger went through.');
    }

    public function testInitValidErrorHandler()
    {
        $validErrorHandler = new DefaultErrorHandler();
        $optlyObject = new Optimizely($this->datafile, null, null, $validErrorHandler);
    }

    public function testInitInvalidErrorHandler()
    {
        $invalidErrorHandler = new InvalidErrorHandler();
        try {
            $optlyObject = new Optimizely($this->datafile, null, null, $invalidErrorHandler);
        } catch (Exception $exception) {
            return;
        } catch (TypeError $exception) {
            return;
        }

        $this->fail('Unexpected behavior. Invalid error handler went through.');
    }

    public function testValidateDatafileInvalidFileJsonValidationNotSkipped()
    {
        $validateInputsMethod = new \ReflectionMethod('Optimizely\Optimizely', 'validateDatafile');
        $validateInputsMethod->setAccessible(true);

        $this->assertFalse(
            $validateInputsMethod->invoke(new Optimizely('Random datafile'),
                'Random datafile',
                false)
        );

        $this->expectOutputRegex('/Provided "datafile" has invalid schema./');
    }

    public function testValidateDatafileInvalidFileJsonValidationSkipped()
    {
        $validateInputsMethod = new \ReflectionMethod('Optimizely\Optimizely', 'validateDatafile');
        $validateInputsMethod->setAccessible(true);

        $this->assertTrue(
            $validateInputsMethod->invoke(new Optimizely('Random datafile', null, null, null, true),
                'Random datafile', true)
        );
    }

    public function testActivateInvalidOptimizelyObject()
    {
        $optlyObject = new Optimizely('Random datafile');
        $optlyObject->activate('some_experiment', 'some_user');
        $this->expectOutputRegex('/Datafile has invalid format. Failing "activate"./');
    }

    public function testActivateInvalidAttributes()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::ERROR, 'Provided attributes are in an invalid format.');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'Not activating user "test_user".');

        $errorHandlerMock = $this->getMockBuilder(NoOpErrorHandler::class)
            ->setMethods(array('handleError'))
            ->getMock();
        $errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidAttributeException('Provided attributes are in an invalid format.'));

        $optlyObject = new Optimizely(
            $this->datafile, new ValidEventDispatcher(), $this->loggerMock, $errorHandlerMock
        );

        // Call activate
        $this->assertNull($optlyObject->activate('test_experiment', 'test_user', 42));
    }

    public function testActivateUserInNoVariation()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        $this->eventBuilderMock->expects($this->never())
            ->method('createImpressionEvent');

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(4))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "not_in_variation_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 8495 to user "not_in_variation_user" with bucketing ID "not_in_variation_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "not_in_variation_user" is in no variation.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'Not activating user "not_in_variation_user".');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call activate
        $this->assertNull($optlyObject->activate('test_experiment', 'not_in_variation_user', $userAttributes));
    }

    public function testActivateNoAudienceNoAttributes()
    {
        $this->eventBuilderMock->expects($this->once())
            ->method('createImpressionEvent')
            ->with(
                $this->projectConfig,
                'group_experiment_1',
                'group_exp_1_var_2', 'user_1', null
            )
            ->willReturn(new LogEvent(
                    'logx.optimizely.com/decision',
                    ['param1' => 'val1', 'param2' => 'val2'], 'POST', [])
            );

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(7))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "user_1" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 1922 to user "user_1" with bucketing ID "user_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "user_1" is in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 9525 to user "user_1" with bucketing ID "user_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "user_1" is in variation group_exp_1_var_2 of experiment group_experiment_1.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Activating user "user_1" in experiment "group_experiment_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Dispatching impression event to URL logx.optimizely.com/decision with params param1=val1&param2=val2.');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);
        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call activate
        $this->assertEquals('group_exp_1_var_2', $optlyObject->activate('group_experiment_1', 'user_1'));
    }

    public function testActivateNoAudienceNoAttributesAfterSetForcedVariation()
    {
        $userId = 'test_user';
        $experimentKey = 'test_experiment';
        $experimentId = '7716830082';
        $variationKey = 'control';
        $variationId = '7722370027';

        $this->eventBuilderMock->expects($this->once())
            ->method('createImpressionEvent')
            ->with(
                $this->projectConfig,
                'group_experiment_1',
                'group_exp_1_var_2', 'user_1', null
            )
            ->willReturn(new LogEvent(
                    'logx.optimizely.com/decision',
                    ['param1' => 'val1', 'param2' => 'val2'], 'POST', [])
            );

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(8))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Set variation "%s" for experiment "%s" and user "%s" in the forced variation map.', $variationId, $experimentId, $userId));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "user_1" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 1922 to user "user_1" with bucketing ID "user_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "user_1" is in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 9525 to user "user_1" with bucketing ID "user_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "user_1" is in variation group_exp_1_var_2 of experiment group_experiment_1.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Activating user "user_1" in experiment "group_experiment_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Dispatching impression event to URL logx.optimizely.com/decision with params param1=val1&param2=val2.');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);
        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // set forced variation
        $this->assertTrue($this->optimizelyObject->setForcedVariation($experimentKey, $userId, $variationKey), 'Set variation for paused experiment should have failed.');

        // Call activate
        $this->assertEquals('group_exp_1_var_2', $optlyObject->activate('group_experiment_1', 'user_1'));
    }

    public function testActivateAudienceNoAttributes()
    {
        $this->eventBuilderMock->expects($this->never())
            ->method('createImpressionEvent');

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(3))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not activating user "test_user".');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call activate
        $this->assertNull($optlyObject->activate('test_experiment', 'test_user'));
    }

    public function testActivateWithAttributes()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        $this->eventBuilderMock->expects($this->once())
            ->method('createImpressionEvent')
            ->with(
                $this->projectConfig,
                'test_experiment',
                'control', 'test_user', $userAttributes
            )
            ->willReturn(new LogEvent('logx.optimizely.com/decision', ['param1' => 'val1'], 'POST', []));
        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(5))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 3037 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in variation control of experiment test_experiment.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'Activating user "test_user" in experiment "test_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Dispatching impression event to URL logx.optimizely.com/decision with params param1=val1.');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call activate
        $this->assertEquals('control', $optlyObject->activate('test_experiment', 'test_user', $userAttributes));
    }

    public function testActivateExperimentNotRunning()
    {
        $this->eventBuilderMock->expects($this->never())
            ->method('createImpressionEvent');

        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, 'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO,
                'Not activating user "test_user".');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call activate
        $this->assertNull($optlyObject->activate('paused_experiment', 'test_user', null));
    }

    public function testGetVariationInvalidOptimizelyObject()
    {
        $optlyObject = new Optimizely('Random datafile');
        $optlyObject->getVariation('some_experiment', 'some_user');
        $this->expectOutputRegex('/Datafile has invalid format. Failing "getVariation"./');
    }

    public function testGetVariationInvalidAttributes()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Provided attributes are in an invalid format.');

        $errorHandlerMock = $this->getMockBuilder(NoOpErrorHandler::class)
            ->setMethods(array('handleError'))
            ->getMock();
        $errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidAttributeException('Provided attributes are in an invalid format.'));

        $optlyObject = new Optimizely(
            $this->datafile, new ValidEventDispatcher(), $this->loggerMock, $errorHandlerMock
        );

        // Call activate
        $this->assertNull($optlyObject->getVariation('test_experiment', 'test_user', 42));
    }

    public function testGetVariationAudienceMatch()
    {
        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(3))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 3037 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in variation control of experiment test_experiment.');

        $this->assertEquals(
            'control',
            $this->optimizelyObject->getVariation(
                'test_experiment',
                'test_user',
                ['device_type' => 'iPhone', 'location' => 'San Francisco']
            )
        );
    }

    public function testGetVariationAudienceMatchAfterSetForcedVariation()
    {
        $userId = 'test_user';
        $experimentKey = 'test_experiment';
        $experimentId = '7716830082';
        $variationKey = 'control';
        $variationId = '7722370027';
        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'San Francisco',
        ];

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Set variation "%s" for experiment "%s" and user "%s" in the forced variation map.', $variationId, $experimentId, $userId));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Variation "%s" is mapped to experiment "%s" and user "%s" in the forced variation map', $variationKey, $experimentKey, $userId));

        $this->assertTrue($this->optimizelyObject->setForcedVariation($experimentKey, $userId, $variationKey), 'Set variation for paused experiment should have failed.');
        $this->assertEquals($variationKey, $this->optimizelyObject->getVariation($experimentKey, $userId));
    }

    public function testGetVariationAudienceNoMatch()
    {
        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".');

        $this->assertNull($this->optimizelyObject->getVariation('test_experiment', 'test_user'));
    }

    public function testGetVariationExperimentNotRunning()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::INFO, 'Experiment "paused_experiment" is not running.');

        $this->assertNull($this->optimizelyObject->getVariation('paused_experiment', 'test_user'));
    }

    public function testGetVariationExperimentNotRunningAfterSetForceVariation()
    {
        $userId = 'test_user';
        $experimentKey = 'paused_experiment';
        $experimentId = '7716830585';
        $variationKey = 'control';
        $variationId = '7722370427';
        $callIndex = 0;

        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Set variation "%s" for experiment "%s" and user "%s" in the forced variation map.', $variationId, $experimentId, $userId));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'Experiment "paused_experiment" is not running.');

        $this->assertTrue($this->optimizelyObject->setForcedVariation($experimentKey, $userId, $variationKey), 'Set variation for paused experiment should have failed.');
        $this->assertNull($this->optimizelyObject->getVariation($experimentKey, $userId));
    }

    public function testGetVariationUserInForcedVariationInExperiment()
    {
        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "user1" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "user1" is forced in variation "control" of experiment "test_experiment".');

        $this->assertEquals(
            'control',
            $this->optimizelyObject->getVariation('test_experiment', 'user1')
        );
    }

    public function testGetVariationWhitelistedUserAfterSetForcedVariation()
    {
        $userId = 'user1';
        $experimentKey = 'test_experiment';
        $experimentId = '7716830082';
        $variationKey = 'variation';
        $variationId = '7721010009';

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Set variation "%s" for experiment "%s" and user "%s" in the forced variation map.', $variationId, $experimentId, $userId));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Variation "%s" is mapped to experiment "%s" and user "%s" in the forced variation map',$variationKey, $experimentKey, $userId));

        $this->assertTrue($this->optimizelyObject->setForcedVariation($experimentKey, $userId, $variationKey), 'Set variation for paused experiment should have passed.');
        $this->assertEquals($this->optimizelyObject->getVariation($experimentKey, $userId), $variationKey);
    }

    public function testGetVariationUserNotInForcedVariationNotInExperiment()
    {
        // Last check to happen is the audience condition check so we make sure we get to that check since
        // the user is not whitelisted
        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".');

        $this->assertNull($this->optimizelyObject->getVariation('test_experiment', 'test_user'));
    }

    public function testValidatePreconditionsUserNotInForcedVariationInExperiment()
    {
        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(3))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 3037 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" is in variation control of experiment test_experiment.');

        $this->assertEquals(
            'control',
            $this->optimizelyObject->getVariation(
                'test_experiment',
                'test_user',
                ['device_type' => 'iPhone', 'location' => 'San Francisco']
            )
        );
    }

    public function testTrackInvalidOptimizelyObject()
    {
        $optlyObject = new Optimizely('Random datafile');
        $optlyObject->track('some_event', 'some_user');
        $this->expectOutputRegex('/Datafile has invalid format. Failing "track"./');
    }

    public function testTrackInvalidAttributes()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Provided attributes are in an invalid format.');

        $errorHandlerMock = $this->getMockBuilder(NoOpErrorHandler::class)
            ->setMethods(array('handleError'))
            ->getMock();
        $errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidAttributeException('Provided attributes are in an invalid format.'));

        $optlyObject = new Optimizely(
            $this->datafile, new ValidEventDispatcher(), $this->loggerMock, $errorHandlerMock
        );

        // Call activate
        $this->assertNull($optlyObject->track('purchase', 'test_user', 42));
    }

    public function testTrackNoAttributesNoEventValue()
    {
        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'purchase',
                ["7718750065" => "7725250007"],
                'test_user',
                null,
                null
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(16))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "test_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is not in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "group_experiment_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in experiment group_experiment_2 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 9871 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in variation group_exp_2_var_2 of experiment group_experiment_2.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "paused_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Tracking event "purchase" for user "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Dispatching conversion event to URL logx.optimizely.com/track with params param1=val1.');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call track
        $optlyObject->track('purchase', 'test_user');
    }

    public function testTrackNoAttributesNoEventValueAfterSetForcedVariation()
    {
        $userId = 'test_user';
        $experimentKey = 'test_experiment';
        $experimentId = '7716830082';
        $variationKey = 'control';
        $variationId = '7722370027';

        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'purchase',
                ["7718750065" => "7725250007"],
                'test_user',
                null,
                null
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(17))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Set variation "%s" for experiment "%s" and user "%s" in the forced variation map.', $variationId, $experimentId, $userId));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "test_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is not in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "group_experiment_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in experiment group_experiment_2 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 9871 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in variation group_exp_2_var_2 of experiment group_experiment_2.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "paused_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Tracking event "purchase" for user "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Dispatching conversion event to URL logx.optimizely.com/track with params param1=val1.');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $this->assertTrue($this->optimizelyObject->setForcedVariation($experimentKey, $userId, $variationKey), 'Set variation for paused experiment should have failed.');

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call track
        $optlyObject->track('purchase', 'test_user');
    }

    public function testTrackWithAttributesNoEventValue()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'purchase',
                [
                    '7716830082' => '7722370027',
                    '7718750065' => '7725250007'
                ],
                'test_user',
                $userAttributes,
                null
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(16))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 3037 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in variation control of experiment test_experiment.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is not in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "group_experiment_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in experiment group_experiment_2 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 9871 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in variation group_exp_2_var_2 of experiment group_experiment_2.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "paused_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Tracking event "purchase" for user "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Dispatching conversion event to URL logx.optimizely.com/track with params param1=val1.');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call track
        $optlyObject->track('purchase', 'test_user', $userAttributes);
    }

    public function testTrackNoAttributesWithDeprecatedEventValue()
    {
        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'purchase',
                ['7718750065' => '7725250007'],
                'test_user',
                null,
                array('revenue' => 42)
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(17))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::WARNING,
                'Event value is deprecated in track call. Use event tags to pass in revenue value instead.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "test_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is not in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "group_experiment_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in experiment group_experiment_2 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 9871 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in variation group_exp_2_var_2 of experiment group_experiment_2.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "paused_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Tracking event "purchase" for user "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Dispatching conversion event to URL logx.optimizely.com/track with params param1=val1.');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call track
        $optlyObject->track('purchase', 'test_user', null, 42);
    }

    public function testTrackNoAttributesWithEventValue()
    {
        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'purchase',
                ['7718750065' => '7725250007'],
                'test_user',
                null,
                array('revenue' => 42)
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(16))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "test_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is not in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "group_experiment_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in experiment group_experiment_2 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 9871 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in variation group_exp_2_var_2 of experiment group_experiment_2.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "paused_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Tracking event "purchase" for user "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Dispatching conversion event to URL logx.optimizely.com/track with params param1=val1.');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call track
        $optlyObject->track('purchase', 'test_user', null, array('revenue' => 42));
    }

    public function testTrackNoAttributesWithInvalidEventValue()
    {
        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'purchase',
                ['7718750065' => '7725250007'],
                'test_user',
                null,
                array('revenue' => '4200')
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(16))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'Not tracking user "test_user" for experiment "test_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" is not in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'Not tracking user "test_user" for experiment "group_experiment_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" is in experiment group_experiment_2 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 9871 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" is in variation group_exp_2_var_2 of experiment group_experiment_2.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'Not tracking user "test_user" for experiment "paused_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'Tracking event "purchase" for user "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Dispatching conversion event to URL logx.optimizely.com/track with params param1=val1.');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call track
        $optlyObject->track('purchase', 'test_user', null, array('revenue' => '4200'));
    }

    public function testTrackWithAttributesWithDeprecatedEventValue()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'purchase',
                [
                    '7716830082' => '7722370027',
                    '7718750065' => '7725250007'
                ],
                'test_user',
                $userAttributes,
                array('revenue' => 42)
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(17))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::WARNING,
                'Event value is deprecated in track call. Use event tags to pass in revenue value instead.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 3037 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in variation control of experiment test_experiment.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is not in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "group_experiment_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in experiment group_experiment_2 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 9871 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in variation group_exp_2_var_2 of experiment group_experiment_2.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "paused_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Tracking event "purchase" for user "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Dispatching conversion event to URL logx.optimizely.com/track with params param1=val1.');


        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call track
        $optlyObject->track('purchase', 'test_user', $userAttributes, 42);
    }

    public function testTrackWithAttributesWithEventValue()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'purchase',
                [
                    '7716830082' => '7722370027',
                    '7718750065' => '7725250007'
                ],
                'test_user',
                $userAttributes,
                array('revenue' => 42)
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(16))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 3037 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in variation control of experiment test_experiment.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is not in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "group_experiment_1".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "test_user" is not in the forced variation map.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 4517 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in experiment group_experiment_2 of group 7722400015.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 9871 to user "test_user" with bucketing ID "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in variation group_exp_2_var_2 of experiment group_experiment_2.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "paused_experiment".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO,
                'Tracking event "purchase" for user "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG,
                'Dispatching conversion event to URL logx.optimizely.com/track with params param1=val1.');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call track
        $optlyObject->track('purchase', 'test_user', $userAttributes, array('revenue' => 42));
    }

    // check that a null variation key clears the forced variation
    public function testSetForcedVariationNullVariation()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'San Francisco'
        ];

        $optlyObject->activate('test_experiment', 'test_user', $userAttributes);

        // set variation
        $this->assertTrue($optlyObject->setForcedVariation('test_experiment', 'test_user', 'variation'), 'Set forced variation to variation failed.');
        $forcedVariationKey = $optlyObject->getVariation('test_experiment', 'test_user', $userAttributes);
        $this->assertEquals('variation', $forcedVariationKey, sprintf('Forced variation key should be variation, but got "%s".', $forcedVariationKey));

        // clear variation and check that the user gets bucketed normally
        $this->assertTrue($optlyObject->setForcedVariation('test_experiment', 'test_user', null), 'Clear forced variation failed.');
        $forcedVariationKey = $optlyObject->getVariation('test_experiment', 'test_user', $userAttributes);
        $this->assertEquals('control', $forcedVariationKey, sprintf('Forced variation key should be control, but got "%s".', $forcedVariationKey));
    }

    public function testSetForcedVariationWithValidConditions()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'San Francisco'
        ];

        $variationKey = $optlyObject->getVariation('test_experiment', 'test_user', $userAttributes);
        $this->assertEquals('control', $variationKey, sprintf('Invalid variation "%s" for baseline check.', $variationKey));

        // valid experiment, valid variation, valid user ID --> the user should be bucketed to the specified forced variation
        $this->assertTrue($optlyObject->setForcedVariation('test_experiment', 'test_user', 'variation'), 'Set variation for valid conditions should have succeeded.');
        $variationKey = $optlyObject->getVariation('test_experiment', 'test_user', $userAttributes);
        $this->assertEquals('variation', $variationKey, sprintf('Invalid variation "%s" for valid conditions.', $variationKey));
    }

    public function testSetForcedVariationWithInvalidUserIDs()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'San Francisco'
        ];

        // null user ID --> set should fail, normal bucketing  [TODO (Alda): getVariation on a null userID should return null]
        $this->assertFalse($optlyObject->setForcedVariation('test_experiment', null, 'bad_variation'), 'Set variation for null user ID should have failed.');
        $variationKey = $optlyObject->getVariation('test_experiment', null, $userAttributes);
        $this->assertEquals('variation', $variationKey, sprintf('Invalid variation "%s" for null user ID.', $variationKey));
        // empty string user ID --> set should fail, normal bucketing [TODO (Alda): getVariation on an empty userID should return null]
        $this->assertFalse($optlyObject->setForcedVariation('test_experiment', '', 'bad_variation'), 'Set variation for empty string user ID should have failed.');
        $variationKey = $optlyObject->getVariation('test_experiment', '', $userAttributes);
        $this->assertEquals('variation', $variationKey, sprintf('Invalid variation "%s" for empty string user ID.', $variationKey));
    }

    public function testSetForcedVariationWithInvalidExperimentKeys()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'San Francisco'
        ];

        // experiment key not in datafile --> set should fail
        $this->assertFalse($optlyObject->setForcedVariation('bad_experiment', 'test_user', 'control'), 'Set variation for experiment key not in the datafile should have failed.');
        $variationKey = $optlyObject->getVariation('bad_experiment', 'test_user', $userAttributes);
        $this->assertNull($variationKey, sprintf('Invalid variation "%s" for experiment key not in the datafile.', $variationKey));
        // null experiment key --> set should fail
        $this->assertFalse($optlyObject->setForcedVariation(null, 'test_user', 'control'), 'Set variation for null experiment key should have failed.');
        $variationKey = $optlyObject->getVariation(null, 'test_user', $userAttributes);
        $this->assertNull($variationKey, sprintf('Invalid variation "%s" for null experiment key.', $variationKey));
        // empty string experiment key --> set should fail
        $this->assertFalse($optlyObject->setForcedVariation('', 'test_user', 'control'), 'Set variation for empty string experiment key should have failed.');
        $variationKey = $optlyObject->getVariation('', 'test_user', $userAttributes);
        $this->assertNull($variationKey, sprintf('Invalid variation "%s" for empty string experiment key.', $variationKey));
    }

    public function testSetForcedVariationWithInvalidVariationKeys()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'San Francisco'
        ];

        // variation key not in datafile --> set should fail, normal bucketing
        $this->assertFalse($optlyObject->setForcedVariation('test_experiment', 'test_user', 'bad_variation'), 'Set variation with variation not in datafile should have failed.');
        $variationKey = $optlyObject->getVariation('test_experiment', 'test_user', $userAttributes);
        $this->assertEquals('control', $variationKey, sprintf('Invalid variation "%s" for variation key not in datafile.', $variationKey));
        // null variation key --> set should succeed (the variation is reset), normal bucketing
        $this->assertTrue($optlyObject->setForcedVariation('test_experiment', 'test_user', null), 'Set variation with null variation key should have succeeded.');
        $variationKey = $optlyObject->getVariation('test_experiment', 'test_user', $userAttributes);
        $this->assertEquals('control', $variationKey, sprintf('Invalid variation "%s" for null variation key.', $variationKey));
        // empty string variation key --> set should fail, normal bucketing
        $this->assertFalse($optlyObject->setForcedVariation('test_experiment', 'test_user', ''), 'Set variation with empty string variation key should have failed.');
        $variationKey = $optlyObject->getVariation('test_experiment', 'test_user', $userAttributes);
        $this->assertEquals('control', $variationKey, sprintf('Invalid variation "%s" for empty string variation key.', $variationKey));
    }

    public function testSetForcedVariationWithMultipleSets()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'San Francisco'
        ];

        $this->assertTrue($optlyObject->setForcedVariation('test_experiment', 'test_user', 'control'), 'Set variation should have succeeded.');
        $variationKey = $optlyObject->getVariation('test_experiment', 'test_user', $userAttributes);
        $this->assertEquals('control', $variationKey, sprintf('Invalid variation "%s" for multiple sets on same user and same experiment.', $variationKey));

        // same user, same experiment --> set should succeed
        $this->assertTrue($optlyObject->setForcedVariation('group_experiment_1', 'test_user', 'group_exp_1_var_1'), 'Set variation for multiple sets on same user and same experiment should have succeeded.');
        $variationKey = $optlyObject->getVariation('group_experiment_1', 'test_user', $userAttributes);
        $this->assertEquals('group_exp_1_var_1', $variationKey, sprintf('Invalid variation "%s" for multiple sets on same user and same experiment.', $variationKey));
        // same user, different experiment --> set should succeed
        $this->assertTrue($optlyObject->setForcedVariation('group_experiment_1', 'test_user', 'group_exp_1_var_1'), 'Set variation for multiple sets on same user and different experiment have succeeded.');
        $variationKey = $optlyObject->getVariation('group_experiment_1', 'test_user', $userAttributes);
        $this->assertEquals('group_exp_1_var_1', $variationKey, sprintf('Invalid variation "%s" for multiple sets on same user and different experiment.', $variationKey));
        // different user --> set should succeed
        $this->assertTrue($optlyObject->setForcedVariation('group_experiment_1', 'test_user2', 'group_exp_1_var_1'), 'Set variation for multiple sets on a different user should have succeeded.');
        $variationKey = $optlyObject->getVariation('group_experiment_1', 'test_user2', $userAttributes);
        $this->assertEquals('group_exp_1_var_1', $variationKey, sprintf('Invalid variation "%s" for multiple sets on a different user.', $variationKey));
    }

    public function testGetForcedVariationWithValidConditions()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $this->assertTrue($optlyObject->setForcedVariation('test_experiment', 'test_user', 'variation'), 'Set variation to "variation" failed.');
        // call getForcedVariation with valid experiment key and user ID
        $forcedVariationKey = $optlyObject->getForcedVariation('test_experiment', 'test_user');
        $this->assertEquals('variation', $forcedVariationKey);
    }


    public function testGetForcedVariationWithInvalidExperimentKeys()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        // call getForcedVariation with invalid experiment
        $forcedVariationKey = $optlyObject->getForcedVariation('invalid_experiment', 'test_user');
        $this->assertNull($forcedVariationKey);
        // call getForcedVariation with null experiment key
        $forcedVariationKey = $optlyObject->getForcedVariation(null, 'test_user');
        $this->assertNull($forcedVariationKey);
        // call getForcedVariation with empty experiment key
        $forcedVariationKey = $optlyObject->getForcedVariation('', 'test_user');
        $this->assertNull($forcedVariationKey);
    }

    public function testGetForcedVariationWithInvalidUserIDs()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        // call getForcedVariation invalid userID
        $forcedVariationKey = $optlyObject->getForcedVariation('test_experiment', 'invalid_user');
        $this->assertNull($forcedVariationKey);
        // call getForcedVariation with null userID
        $forcedVariationKey = $optlyObject->getForcedVariation('test_experiment', null);
        $this->assertNull($forcedVariationKey);
        // call getForcedVariation with empty userID
        $forcedVariationKey = $optlyObject->getForcedVariation('test_experiment', '');
        $this->assertNull($forcedVariationKey);
    }

    public function testGetForcedVariationWithExperimentNotRunning()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        // call getForcedVariation with an experiment that's not running
        $this->assertTrue($optlyObject->setForcedVariation('paused_experiment', 'test_user', 'variation'), 'Set variation to "variation" failed.');
        $forcedVariationKey = $optlyObject->getForcedVariation('paused_experiment', 'test_user');
        $this->assertEquals('variation', $forcedVariationKey);
    }

    public function testGetForcedVariationWithMultipleSets()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $this->assertTrue($optlyObject->setForcedVariation('test_experiment', 'test_user', 'control'), 'Set variation should have succeeded.');
        $variationKey = $optlyObject->getForcedVariation('test_experiment', 'test_user');
        $this->assertEquals('control', $variationKey, sprintf('Invalid variation "%s" for multiple sets on same user and same experiment.', $variationKey));

        // same user, same experiment --> set should succeed
        $this->assertTrue($optlyObject->setForcedVariation('group_experiment_1', 'test_user', 'group_exp_1_var_1'), 'Set variation for multiple sets on same user and same experiment should have succeeded.');
        $variationKey = $optlyObject->getForcedVariation('group_experiment_1', 'test_user');
        $this->assertEquals('group_exp_1_var_1', $variationKey, sprintf('Invalid variation "%s" for multiple sets on same user and same experiment.', $variationKey));
        // same user, different experiment --> set should succeed
        $this->assertTrue($optlyObject->setForcedVariation('group_experiment_1', 'test_user', 'group_exp_1_var_1'), 'Set variation for multiple sets on same user and different experiment have succeeded.');
        $variationKey = $optlyObject->getForcedVariation('group_experiment_1', 'test_user');
        $this->assertEquals('group_exp_1_var_1', $variationKey, sprintf('Invalid variation "%s" for multiple sets on same user and different experiment.', $variationKey));
        // different user --> set should succeed
        $this->assertTrue($optlyObject->setForcedVariation('group_experiment_1', 'test_user2', 'group_exp_1_var_1'), 'Set variation for multiple sets on a different user should have succeeded.');
        $variationKey = $optlyObject->getForcedVariation('group_experiment_1', 'test_user2');
        $this->assertEquals('group_exp_1_var_1', $variationKey, sprintf('Invalid variation "%s" for multiple sets on a different user.', $variationKey));
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

        $this->loggerMock->expects($this->exactly(5))
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
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User ID is invalid');

        $optlyObject = new Optimizely(DATAFILE, new ValidEventDispatcher(), $this->loggerMock);

        $optlyObject->setForcedVariation($invalidExperimentKey, $userId, $variationKey);
        $optlyObject->setForcedVariation($experimentKey, $userId, null);
        $optlyObject->setForcedVariation($experimentKey, $userId, $invalidVariationKey );
        $optlyObject->setForcedVariation($experimentKey, $userId, $variationKey);
        $optlyObject->setForcedVariation($experimentKey, null, $variationKey);
    }

    // test that all the logs in getForcedVariation are getting called
    public function testGetForcedVariationLogs()
    {
        $userId = 'test_user';
        $invalidUserId = 'invalid_user';
        $experimentKey = 'test_experiment';
        $experimentId = '7716830082';
        $invalidExperimentKey = 'invalid_experiment';
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
            ->with(Logger::DEBUG, sprintf('Variation "%s" is mapped to experiment "%s" and user "%s" in the forced variation map', $variationKey, $experimentKey, $userId));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User ID is invalid');

        $optlyObject = new Optimizely(DATAFILE, new ValidEventDispatcher(), $this->loggerMock);

        $optlyObject->setForcedVariation($experimentKey, $userId, $variationKey);
        $optlyObject->getForcedVariation($experimentKey, $invalidUserId);
        $optlyObject->getForcedVariation($invalidExperimentKey, $userId);
        $optlyObject->getForcedVariation($experimentKey, $userId);
        $optlyObject->getForcedVariation($experimentKey, null);

    }

    public function testGetVariationBucketingIdAttribute()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco',
        ];

        $userAttributesWithBucketingId = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco',
            RESERVED_ATTRIBUTE_KEY_BUCKETING_ID => $this->testBucketingIdVariation
        ];

        $optlyObject = new Optimizely(DATAFILE, new ValidEventDispatcher(), $this->loggerMock);

        // confirm that a valid variation is bucketed without the bucketing ID
        $variationKey = $optlyObject->getVariation($this->experimentKey, $this->userId, $userAttributes);
        $this->assertEquals($this->variationKeyControl, $variationKey, sprintf('Invalid variation key "%s" for getVariation.', $variationKey));

        // confirm that invalid audience returns null
        $variationKey = $optlyObject->getVariation($this->experimentKey, $this->userId);
        $this->assertEquals(null, $variationKey, sprintf('Invalid variation key "%s" for getVariation with bucketing ID "%s".', $variationKey, $this->testBucketingIdControl));

        // confirm that a valid variation is bucketed with the bucketing ID
        $variationKey = $optlyObject->getVariation($this->experimentKey, $this->userId, $userAttributesWithBucketingId);
        $this->assertEquals($this->variationKeyVariation, $variationKey, sprintf('Invalid variation key "%s" for getVariation with bucketing ID "%s".', $variationKey, $this->testBucketingIdVariation));

        // confirm that invalid experiment with the bucketing ID returns null
        $variationKey = $optlyObject->getVariation("invalidExperimentKey", $this->userId, $userAttributesWithBucketingId);
        $this->assertEquals(null, $variationKey, sprintf('Invalid variation key "%s" for getVariation with bucketing ID "%s".', $variationKey, $this->testBucketingIdControl));
    }

}
