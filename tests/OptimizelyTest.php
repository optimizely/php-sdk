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

use Exception;
use Monolog\Logger;
use Optimizely\Bucketer;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Event\LogEvent;
use Optimizely\Exceptions\InvalidAttributeException;
use Optimizely\Logger\NoOpLogger;
use Optimizely\ProjectConfig;
use TypeError;
use Optimizely\ErrorHandler\DefaultErrorHandler;
use Optimizely\Event\Builder\EventBuilder;
use Optimizely\Logger\DefaultLogger;
use Optimizely\Optimizely;


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

        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();
        $this->optimizelyObject = new Optimizely($this->datafile, null, $this->loggerMock);

        $this->projectConfig = new ProjectConfig($this->datafile, $this->loggerMock, new NoOpErrorHandler());

        // Mock EventBuilder
        $this->eventBuilderMock = $this->getMockBuilder(EventBuilder::class)
            ->setMethods(array('createImpressionEvent', 'createConversionEvent'))
            ->setConstructorArgs(array(new Bucketer($this->loggerMock)))
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
        try
        {
            $optlyObject = new Optimizely($this->datafile, $invalidDispatcher);
        }
        catch (Exception $exception)
        {
            return;
        }
        catch (TypeError $exception)
        {
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
        try
        {
            $optlyObject = new Optimizely($this->datafile, null, $invalidLogger);
        }
        catch (Exception $exception)
        {
            return;
        }
        catch (TypeError $exception)
        {
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
        try
        {
            $optlyObject = new Optimizely($this->datafile, null, null, $invalidErrorHandler);
        }
        catch (Exception $exception)
        {
            return;
        }
        catch (TypeError $exception)
        {
            return;
        }

        $this->fail('Unexpected behavior. Invalid error handler went through.');
    }

    public function testValidateInputsInvalidFileJsonValidationNotSkipped()
    {
        $validateInputsMethod = new \ReflectionMethod('Optimizely\Optimizely', 'validateInputs');
        $validateInputsMethod->setAccessible(true);

        $this->assertFalse(
            $validateInputsMethod->invoke(new Optimizely('Random datafile'),
            'Random datafile',
            false)
        );

        $this->expectOutputRegex('/Provided "datafile" has invalid schema./');
    }

    public function testValidateInputsInvalidFileJsonValidationSkipped()
    {
        $validateInputsMethod = new \ReflectionMethod('Optimizely\Optimizely', 'validateInputs');
        $validateInputsMethod->setAccessible(true);

        $this->assertTrue(
            $validateInputsMethod->invoke(new Optimizely('Random datafile', null, null, null, true),
            'Random datafile', true)
        );

        $this->expectOutputRegex('/Provided "datafile" is in an invalid format./');
    }

    public function testValidatePreconditionsExperimentNotRunning()
    {
        $validatePreconditions = new \ReflectionMethod('Optimizely\Optimizely', 'validatePreconditions');
        $validatePreconditions->setAccessible(true);

        $this->assertFalse(
            $validatePreconditions->invoke(
                $this->optimizelyObject,
                $this->projectConfig->getExperimentFromKey('paused_experiment'),
                'test_user',
                [])
        );
    }

    public function testValidatePreconditionsExperimentRunning()
    {
        $validatePreconditions = new \ReflectionMethod('Optimizely\Optimizely', 'validatePreconditions');
        $validatePreconditions->setAccessible(true);

        $this->assertTrue(
            $validatePreconditions->invoke(
                $this->optimizelyObject,
                $this->projectConfig->getExperimentFromKey('test_experiment'),
                'test_user',
                ['device_type' => 'iPhone', 'location' => 'San Francisco'])
        );
    }

    public function testValidatePreconditionsUserInForcedVariationNotInExperiment()
    {
        $validatePreconditions = new \ReflectionMethod('Optimizely\Optimizely', 'validatePreconditions');
        $validatePreconditions->setAccessible(true);

        $this->assertTrue(
            $validatePreconditions->invoke(
                $this->optimizelyObject,
                $this->projectConfig->getExperimentFromKey('test_experiment'),
                'user1',
                [])
        );
    }

    public function testValidatePreconditionsUserInForcedVariationInExperiment()
    {
        $validatePreconditions = new \ReflectionMethod('Optimizely\Optimizely', 'validatePreconditions');
        $validatePreconditions->setAccessible(true);

        $this->assertTrue(
            $validatePreconditions->invoke(
                $this->optimizelyObject,
                $this->projectConfig->getExperimentFromKey('test_experiment'),
                'user1',
                [])
        );
    }

    public function testValidatePreconditionsUserNotInForcedVariationNotInExperiment()
    {
        $validatePreconditions = new \ReflectionMethod('Optimizely\Optimizely', 'validatePreconditions');
        $validatePreconditions->setAccessible(true);

        $this->assertFalse(
            $validatePreconditions->invoke(
                $this->optimizelyObject,
                $this->projectConfig->getExperimentFromKey('test_experiment'),
                'test_user',
                [])
        );
    }

    public function testValidatePreconditionsUserNotInForcedVariationInExperiment()
    {
        $validatePreconditions = new \ReflectionMethod('Optimizely\Optimizely', 'validatePreconditions');
        $validatePreconditions->setAccessible(true);

        $this->assertTrue(
            $validatePreconditions->invoke(
                $this->optimizelyObject,
                $this->projectConfig->getExperimentFromKey('test_experiment'),
                'test_user',
                ['device_type' => 'iPhone', 'location' => 'San Francisco'])
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

    public function testActivateNoAudienceNoAttributes()
    {
        $this->eventBuilderMock->expects($this->once())
            ->method('createImpressionEvent')
            ->with(
                $this->projectConfig,
                $this->projectConfig->getExperimentFromKey('group_experiment_1'),
                '7722360022', 'user_1', null
            )
            ->willReturn(new LogEvent(
                'logx.optimizely.com/decision',
                ['param1' => 'val1', 'param2' => 'val2'], 'POST', [])
            );

        $this->loggerMock->expects($this->exactly(6))
            ->method('log');
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 1922 to user "user_1".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO,
                'User "user_1" is in experiment group_experiment_1 of group 7722400015.');
        $this->loggerMock->expects($this->at(2))
            ->method('log')
            ->with(Logger::DEBUG,
                'Assigned bucket 9525 to user "user_1".');
        $this->loggerMock->expects($this->at(3))
            ->method('log')
            ->with(Logger::INFO,
                'User "user_1" is in variation group_exp_1_var_2 of experiment group_experiment_1.');
        $this->loggerMock->expects($this->at(4))
            ->method('log')
            ->with(Logger::INFO,
                'Activating user "user_1" in experiment "group_experiment_1".');
        $this->loggerMock->expects($this->at(5))
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

    public function testActivateAudienceNoAttributes()
    {
        $this->eventBuilderMock->expects($this->never())
            ->method('createImpressionEvent');

        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".');
        $this->loggerMock->expects($this->at(1))
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
                $this->projectConfig->getExperimentFromKey('test_experiment'),
                '7722370027', 'test_user', $userAttributes
            )
            ->willReturn(new LogEvent('logx.optimizely.com/decision', ['param1' => 'val1'], 'POST', []));

        $this->loggerMock->expects($this->exactly(4))
            ->method('log');
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 3037 to user "test_user".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO,
                'User "test_user" is in variation control of experiment test_experiment.');
        $this->loggerMock->expects($this->at(2))
            ->method('log')
            ->with(Logger::INFO, 'Activating user "test_user" in experiment "test_experiment".');
        $this->loggerMock->expects($this->at(3))
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
        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, 'Assigned bucket 3037 to user "test_user".');
        $this->loggerMock->expects($this->at(1))
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

    public function testGetVariationAudienceNoMatch()
    {
        $this->loggerMock->expects($this->once())
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
                [$this->projectConfig->getExperimentFromKey('group_experiment_1'),
                    $this->projectConfig->getExperimentFromKey('group_experiment_2')],
                'test_user',
                null,
                null
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $this->loggerMock->expects($this->exactly(6))
            ->method('log');
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "test_experiment".');
        $this->loggerMock->expects($this->at(2))
            ->method('log')
            ->with(Logger::INFO,
                'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at(3))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "paused_experiment".');
        $this->loggerMock->expects($this->at(4))
            ->method('log')
            ->with(Logger::INFO,
                'Tracking event "purchase" for user "test_user".');
        $this->loggerMock->expects($this->at(5))
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
                [$this->projectConfig->getExperimentFromKey('test_experiment'),
                    $this->projectConfig->getExperimentFromKey('group_experiment_1'),
                    $this->projectConfig->getExperimentFromKey('group_experiment_2')],
                'test_user',
                $userAttributes,
                null
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $this->loggerMock->expects($this->exactly(4))
            ->method('log');
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, 'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "paused_experiment".');
        $this->loggerMock->expects($this->at(2))
            ->method('log')
            ->with(Logger::INFO,
                'Tracking event "purchase" for user "test_user".');
        $this->loggerMock->expects($this->at(3))
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

    public function testTrackNoAttributesWithEventValue()
    {
        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'purchase',
                [$this->projectConfig->getExperimentFromKey('group_experiment_1'),
                    $this->projectConfig->getExperimentFromKey('group_experiment_2')],
                'test_user',
                null,
                42
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $this->loggerMock->expects($this->exactly(6))
            ->method('log');
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "test_experiment".');
        $this->loggerMock->expects($this->at(2))
            ->method('log')
            ->with(Logger::INFO,
                'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at(3))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "paused_experiment".');
        $this->loggerMock->expects($this->at(4))
            ->method('log')
            ->with(Logger::INFO,
                'Tracking event "purchase" for user "test_user".');
        $this->loggerMock->expects($this->at(5))
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

    public function testTrackWithAttributesWithEventValue()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely'
        ];

        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'purchase',
                [$this->projectConfig->getExperimentFromKey('group_experiment_1'),
                    $this->projectConfig->getExperimentFromKey('group_experiment_2')],
                'test_user',
                $userAttributes,
                42
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $this->loggerMock->expects($this->exactly(6))
            ->method('log');
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "test_experiment".');
        $this->loggerMock->expects($this->at(2))
            ->method('log')
            ->with(Logger::INFO,
                'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at(3))
            ->method('log')
            ->with(Logger::INFO,
                'Not tracking user "test_user" for experiment "paused_experiment".');
        $this->loggerMock->expects($this->at(4))
            ->method('log')
            ->with(Logger::INFO,
                'Tracking event "purchase" for user "test_user".');
        $this->loggerMock->expects($this->at(5))
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
}
