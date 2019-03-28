<?php
/**
 * Copyright 2016-2019, Optimizely
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
use Optimizely\DecisionService\DecisionService;
use Optimizely\DecisionService\FeatureDecision;
use Optimizely\Enums\DecisionInfoTypes;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Event\LogEvent;
use Optimizely\Exceptions\InvalidAttributeException;
use Optimizely\Exceptions\InvalidDatafileVersionException;
use Optimizely\Exceptions\InvalidEventTagException;
use Optimizely\Exceptions\InvalidInputException;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Notification\NotificationCenter;
use Optimizely\Notification\NotificationType;
use Optimizely\ProjectConfig;
use TypeError;
use Optimizely\ErrorHandler\DefaultErrorHandler;
use Optimizely\Event\Builder\EventBuilder;
use Optimizely\Logger\DefaultLogger;
use Optimizely\Optimizely;

class OptimizelyTest extends \PHPUnit_Framework_TestCase
{
    const OUTPUT_STREAM = 'output';

    private $datafile;
    
    private $eventBuilderMock;
    private $loggerMock;
    private $optimizelyObject;
    private $projectConfig;

    public function setUp()
    {
        $this->datafile = DATAFILE;
        $this->typedAudiencesDataFile = DATAFILE_WITH_TYPED_AUDIENCES;
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

        $this->collectedLogs = [];
        $this->collectLogsForAssertion = function ($a, $b) {
            $this->collectedLogs[] = array($a,$b);
        };

        $this->optimizelyObject = new Optimizely($this->datafile, null, $this->loggerMock);
        $this->optimizelyTypedAudienceObject = new Optimizely(
            $this->typedAudiencesDataFile,
            null,
            $this->loggerMock
        );

        $this->projectConfig = new ProjectConfig($this->datafile, $this->loggerMock, new NoOpErrorHandler());
        $this->projectConfigForTypedAudience = new ProjectConfig($this->typedAudiencesDataFile, $this->loggerMock, new NoOpErrorHandler());

        // Mock EventBuilder
        $this->eventBuilderMock = $this->getMockBuilder(EventBuilder::class)
            ->setConstructorArgs(array($this->loggerMock))
            ->setMethods(array('createImpressionEvent', 'createConversionEvent'))
            ->getMock();

        $this->notificationCenterMock = $this->getMockBuilder(NotificationCenter::class)
            ->setConstructorArgs(array($this->loggerMock, new NoOpErrorHandler))
            ->setMethods(array('sendNotifications'))
            ->getMock();
    }

    public function testIsValidForInvalidOptimizelyObject()
    {
        $optlyObject = new Optimizely('Random datafile');
        $this->assertFalse($optlyObject->isValid());
    }

    public function testIsValidForValidOptimizelyObject()
    {
        $optlyObject = new Optimizely($this->datafile);
        $this->assertTrue($optlyObject->isValid());
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

    public function testInitUnSupportedDatafileVersion()
    {
        $errorHandlerMock = $this->getMockBuilder(NoOpErrorHandler::class)
            ->setMethods(array('handleError'))
            ->getMock();
        $errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidDatafileVersionException('This version of the PHP SDK does not support the given datafile version: 5.'));
        $optlyObject = new Optimizely(
            UNSUPPORTED_DATAFILE,
            null,
            new DefaultLogger(Logger::INFO, self::OUTPUT_STREAM),
            $errorHandlerMock,
            true
        );
        $this->expectOutputRegex("/This version of the PHP SDK does not support the given datafile version: 5./");
    }

    public function testInitDatafileInvalidFormat()
    {
        $errorHandlerMock = $this->getMockBuilder(NoOpErrorHandler::class)
            ->setMethods(array('handleError'))
            ->getMock();
        $errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidInputException('Provided datafile is in an invalid format.'));
        $optlyObject = new Optimizely(
            '{"version": "2"}',
            null,
            new DefaultLogger(Logger::INFO, self::OUTPUT_STREAM),
            $errorHandlerMock,
            true
        );
        $this->expectOutputRegex('/Provided datafile is in an invalid format./');
    }

    public function testValidateDatafileInvalidFileJsonValidationNotSkipped()
    {
        $validateInputsMethod = new \ReflectionMethod('Optimizely\Optimizely', 'validateDatafile');
        $validateInputsMethod->setAccessible(true);

        $this->assertFalse(
            $validateInputsMethod->invoke(
                new Optimizely('Random datafile', null, new DefaultLogger(Logger::INFO, self::OUTPUT_STREAM)),
                'Random datafile',
                false
            )
        );

        $this->expectOutputRegex('/Provided "datafile" has invalid schema./');
    }

    public function testValidateDatafileInvalidFileJsonValidationSkipped()
    {
        $validateInputsMethod = new \ReflectionMethod('Optimizely\Optimizely', 'validateDatafile');
        $validateInputsMethod->setAccessible(true);

        $this->assertTrue(
            $validateInputsMethod->invoke(
                new Optimizely('Random datafile', null, null, null, true),
                'Random datafile',
                true
            )
        );
    }

    public function testActivateInvalidOptimizelyObject()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array('Random datafile', null, new DefaultLogger(Logger::INFO, self::OUTPUT_STREAM)))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        // verify that sendImpression isn't called
        $optimizelyMock->expects($this->never())
            ->method('sendImpressionEvent');

        $optimizelyMock->activate('some_experiment', 'some_user');
        $this->expectOutputRegex('/Datafile has invalid format. Failing "activate"./');
    }

    public function testActivateCallsValidateInputs()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile))
            ->setMethods(array('validateInputs'))
            ->getMock();

        $experimentKey = 'test_experiment';
        $userId = 'test_user';
        $inputArray = [
            Optimizely::EXPERIMENT_KEY => $experimentKey,
            Optimizely::USER_ID => $userId
        ];

        // assert that validateInputs gets called with exactly same keys
        $optimizelyMock->expects($this->once())
            ->method('validateInputs')
            ->with($inputArray)
            ->willReturn(false);

        $this->assertNull($optimizelyMock->activate($experimentKey, $userId));
    }

    public function testActivateWithInvalidUserID()
    {
        $this->loggerMock->expects($this->exactly(6))
            ->method('log')
            ->with(Logger::ERROR, sprintf('Provided %s is in an invalid format.', Optimizely::USER_ID));

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, new ValidEventDispatcher(), $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        // verify that sendImpression isn't called
        $optimizelyMock->expects($this->never())
            ->method('sendImpressionEvent');

        // Call activate
        $this->assertNull($optimizelyMock->activate('test_experiment', null));
        $this->assertNull($optimizelyMock->activate('test_experiment', 5));
        $this->assertNull($optimizelyMock->activate('test_experiment', 5.5));
        $this->assertNull($optimizelyMock->activate('test_experiment', true));
        $this->assertNull($optimizelyMock->activate('test_experiment', []));
        $this->assertNull($optimizelyMock->activate('test_experiment', (object) array()));
    }

    public function testActivateWithEmptyUserID()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $this->loggerMock->expects($this->any())
            ->method('log')
            ->will($this->returnCallback($this->collectLogsForAssertion));

        // Verify that sendImpressionEvent is called with expected attributes
        $optimizelyMock->expects($this->exactly(1))
            ->method('sendImpressionEvent')
            ->with('test_experiment', 'variation', '', $userAttributes);

        // Call activate
        $this->assertEquals('variation', $optimizelyMock->activate('test_experiment', '', $userAttributes));

        // Verify Logs
        $this->assertContains([Logger::DEBUG, 'User "" is not in the forced variation map.'], $this->collectedLogs);
        $this->assertContains([Logger::DEBUG, 'Assigned bucket 7428 to user "" with bucketing ID "".'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'User "" is in variation variation of experiment test_experiment.'], $this->collectedLogs);
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

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, new ValidEventDispatcher(), $this->loggerMock, $errorHandlerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        // verify that sendImpression isn't called
        $optimizelyMock->expects($this->never())
            ->method('sendImpressionEvent');

        // Call activate
        $this->assertNull($optimizelyMock->activate('test_experiment', 'test_user', 42));
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

        $this->loggerMock->expects($this->any())
            ->method('log')
            ->will($this->returnCallback($this->collectLogsForAssertion));

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, new ValidEventDispatcher(), $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optimizelyMock, $this->eventBuilderMock);

        // verify that sendImpression isn't called
        $optimizelyMock->expects($this->never())
            ->method('sendImpressionEvent');

        // Call activate
        $this->assertNull($optimizelyMock->activate('test_experiment', 'not_in_variation_user', $userAttributes));

        // Verify Logs
        $this->assertContains([Logger::DEBUG, 'User "not_in_variation_user" is not in the forced variation map.'], $this->collectedLogs);
        $this->assertContains([Logger::DEBUG, 'Assigned bucket 8495 to user "not_in_variation_user" with bucketing ID "not_in_variation_user".'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'User "not_in_variation_user" is in no variation.'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'Not activating user "not_in_variation_user".'], $this->collectedLogs);
    }

    public function testActivateNoAudienceNoAttributes()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, new ValidEventDispatcher(), $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $this->loggerMock->expects($this->any())
            ->method('log')
            ->will($this->returnCallback($this->collectLogsForAssertion));

        // Verify that sendImpression is called with expected params
        $optimizelyMock->expects($this->exactly(1))
            ->method('sendImpressionEvent')
            ->with('group_experiment_1', 'group_exp_1_var_2', 'user_1', null);

        // Call activate
        $this->assertSame('group_exp_1_var_2', $optimizelyMock->activate('group_experiment_1', 'user_1'));

        // Verify Logs
        $this->assertContains([Logger::DEBUG, 'User "user_1" is not in the forced variation map.'], $this->collectedLogs);
        $this->assertContains([Logger::DEBUG, 'Assigned bucket 1922 to user "user_1" with bucketing ID "user_1".'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'User "user_1" is in experiment group_experiment_1 of group 7722400015.'], $this->collectedLogs);
        $this->assertContains([Logger::DEBUG, 'Assigned bucket 9525 to user "user_1" with bucketing ID "user_1".'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'User "user_1" is in variation group_exp_1_var_2 of experiment group_experiment_1.'], $this->collectedLogs);
    }

    public function testActivateNoAudienceNoAttributesAfterSetForcedVariation()
    {
        $userId = 'test_user';
        $experimentKey = 'test_experiment';
        $experimentId = '7716830082';
        $variationKey = 'control';
        $variationId = '7722370027';

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, new ValidEventDispatcher(), $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $this->loggerMock->expects($this->any())
            ->method('log')
            ->will($this->returnCallback($this->collectLogsForAssertion));

        // Verify that sendImpression is called with expected params
        $optimizelyMock->expects($this->exactly(1))
            ->method('sendImpressionEvent')
            ->with('group_experiment_1', 'group_exp_1_var_2', 'user_1', null);

        // set forced variation
        $this->assertTrue($optimizelyMock->setForcedVariation($experimentKey, $userId, $variationKey), 'Set variation for paused experiment should have failed.');

        // Call activate
        $this->assertEquals('group_exp_1_var_2', $optimizelyMock->activate('group_experiment_1', 'user_1'));

        // Verify Logs
        $this->assertContains([Logger::DEBUG, sprintf('Set variation "%s" for experiment "%s" and user "%s" in the forced variation map.', $variationId, $experimentId, $userId)], $this->collectedLogs);
        $this->assertContains([Logger::DEBUG, 'User "user_1" is not in the forced variation map.'], $this->collectedLogs);
        $this->assertContains([Logger::DEBUG, 'Assigned bucket 1922 to user "user_1" with bucketing ID "user_1".'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'User "user_1" is in experiment group_experiment_1 of group 7722400015.'], $this->collectedLogs);
        $this->assertContains([Logger::DEBUG, 'Assigned bucket 9525 to user "user_1" with bucketing ID "user_1".'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'User "user_1" is in variation group_exp_1_var_2 of experiment group_experiment_1.'], $this->collectedLogs);
    }

    public function testActivateAudienceNoAttributes()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        // Verify that sendImpressionEvent is not called
        $optimizelyMock->expects($this->never())
            ->method('sendImpressionEvent');

        $this->loggerMock->expects($this->any())
            ->method('log')
            ->will($this->returnCallback($this->collectLogsForAssertion));

        // Call activate
        $this->assertNull($optimizelyMock->activate('test_experiment', 'test_user'));

        // Verify Logs
        $this->assertContains([Logger::DEBUG, 'User "test_user" is not in the forced variation map.'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'Not activating user "test_user".'], $this->collectedLogs);
    }

    public function testActivateWithAttributes()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $this->loggerMock->expects($this->any())
            ->method('log')
            ->will($this->returnCallback($this->collectLogsForAssertion));

        // Verify that sendImpressionEvent is called with expected attributes
        $optimizelyMock->expects($this->exactly(1))
            ->method('sendImpressionEvent')
            ->with('test_experiment', 'control', 'test_user', $userAttributes);

        // Call activate
        $this->assertEquals('control', $optimizelyMock->activate('test_experiment', 'test_user', $userAttributes));

        // Verify Logs
        $this->assertContains([Logger::DEBUG, 'User "test_user" is not in the forced variation map.'], $this->collectedLogs);
        $this->assertContains([Logger::DEBUG, 'Assigned bucket 3037 to user "test_user" with bucketing ID "test_user".'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'User "test_user" is in variation control of experiment test_experiment.'], $this->collectedLogs);
    }

    public function testActivateWithAttributesOfDifferentTypes()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'San Francisco',
            'boolean'=> true,
            'double'=> 5.5,
            'integer'=> 5
        ];

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $this->loggerMock->expects($this->any())
            ->method('log')
            ->will($this->returnCallback($this->collectLogsForAssertion));

        // Verify that sendImpressionEvent is called with expected attributes
        $optimizelyMock->expects($this->exactly(1))
            ->method('sendImpressionEvent')
            ->with('test_experiment', 'control', 'test_user', $userAttributes);

        // Call activate
        $this->assertEquals('control', $optimizelyMock->activate('test_experiment', 'test_user', $userAttributes));

        // Verify Logs
        $this->assertContains([Logger::DEBUG, 'User "test_user" is not in the forced variation map.'], $this->collectedLogs);
        $this->assertContains([Logger::DEBUG, 'Assigned bucket 3037 to user "test_user" with bucketing ID "test_user".'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'User "test_user" is in variation control of experiment test_experiment.'], $this->collectedLogs);
    }

    public function testActivateWithAttributesTypedAudienceMatch()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->typedAudiencesDataFile , null, null))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $userAttributes = [
            'house' => 'Gryffindor'
        ];

        // Verify that sendImpressionEvent is called with expected attributes
        $optimizelyMock->expects($this->at(0))
            ->method('sendImpressionEvent')
            ->with('typed_audience_experiment', 'A', 'test_user', $userAttributes);

        // Should be included via exact match string audience with id '3468206642'
        $this->assertEquals('A', $optimizelyMock->activate('typed_audience_experiment', 'test_user', $userAttributes));

        $userAttributes = [
            'lasers' => 45.5
        ];

        // Verify that sendImpressionEvent is called with expected attributes
        $optimizelyMock->expects($this->at(0))
            ->method('sendImpressionEvent')
            ->with('typed_audience_experiment', 'A', 'test_user', $userAttributes);

        //Should be included via exact match number audience with id '3468206646'
        $this->assertEquals('A', $optimizelyMock->activate('typed_audience_experiment', 'test_user', $userAttributes));
    }

    public function testActivateWithAttributesTypedAudienceMismatch()
    {
        $userAttributes = [
            'house' => 'Hufflepuff'
        ];

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->typedAudiencesDataFile , null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        // Verify that sendImpressionEvent is not called
        $optimizelyMock->expects($this->never())
            ->method('sendImpressionEvent');

        // Call activate
        $this->assertNull($optimizelyMock->activate('typed_audience_experiment', 'test_user', $userAttributes));
    }

    public function testActivateWithAttributesComplexAudienceMatch()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->typedAudiencesDataFile , null, null))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $userAttributes = [
            'house' => 'Welcome to Slytherin!',
            'lasers' => 45.5
        ];

        // Verify that sendImpressionEvent is called once with expected attributes
        $optimizelyMock->expects($this->exactly(1))
            ->method('sendImpressionEvent')
            ->with('audience_combinations_experiment', 'A', 'test_user', $userAttributes);

        // Should be included via substring match string audience with id '3988293898', and
        // exact match number audience with id '3468206646'
        $this->assertEquals('A', $optimizelyMock->activate('audience_combinations_experiment', 'test_user', $userAttributes));
    }

    public function testActivateWithAttributesComplexAudienceMismatch()
    {
        $userAttributes = [
            'house' => 'Hufflepuff',
            'lasers' => 45.5
        ];

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->typedAudiencesDataFile , null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        // Verify that sendImpressionEvent is not called
        $optimizelyMock->expects($this->never())
            ->method('sendImpressionEvent');

        // Call activate
        $this->assertNull($optimizelyMock->activate('audience_combinations_experiment', 'test_user', $userAttributes));
    }

    public function testActivateExperimentNotRunning()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        // Verify that sendImpressionEvent is not called
        $optimizelyMock->expects($this->never())
            ->method('sendImpressionEvent');

        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, 'Experiment "paused_experiment" is not running.');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(
                Logger::INFO,
                'Not activating user "test_user".'
            );

        // Call activate
        $this->assertNull($optimizelyMock->activate('paused_experiment', 'test_user', null));
    }

    public function testGetVariationInvalidOptimizelyObject()
    {
        $optlyObject = new Optimizely('Random datafile', null, new DefaultLogger(Logger::INFO, self::OUTPUT_STREAM));
        $optlyObject->getVariation('some_experiment', 'some_user');
        $this->expectOutputRegex('/Datafile has invalid format. Failing "getVariation"./');
    }

    public function testGetVariationCallsValidateInputs()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile))
            ->setMethods(array('validateInputs'))
            ->getMock();

        $experimentKey = 'test_experiment';
        $userId = 'test_user';
        $inputArray = [
            Optimizely::EXPERIMENT_KEY => $experimentKey,
            Optimizely::USER_ID => $userId
        ];

        // assert that validateInputs gets called with exactly same keys
        $optimizelyMock->expects($this->once())
            ->method('validateInputs')
            ->with($inputArray)
            ->willReturn(false);

        $this->assertNull($optimizelyMock->getVariation($experimentKey, $userId));
    }

    public function testGetVariationWithInvalidUserID()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        $this->loggerMock->expects($this->exactly(6))
            ->method('log')
            ->with(Logger::ERROR, sprintf('Provided %s is in an invalid format.', Optimizely::USER_ID));

        $optlyObject = new Optimizely(
            $this->datafile,
            new ValidEventDispatcher(),
            $this->loggerMock
        );

        // Call getVariation
        $this->assertNull($optlyObject->getVariation('test_experiment', null, $userAttributes));
        $this->assertNull($optlyObject->getVariation('test_experiment', 5, $userAttributes));
        $this->assertNull($optlyObject->getVariation('test_experiment', 5.5, $userAttributes));
        $this->assertNull($optlyObject->getVariation('test_experiment', false, $userAttributes));
        $this->assertNull($optlyObject->getVariation('test_experiment', [], $userAttributes));
        $this->assertNull($optlyObject->getVariation('test_experiment', (object) array(), $userAttributes));
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
            $this->datafile,
            new ValidEventDispatcher(),
            $this->loggerMock,
            $errorHandlerMock
        );

        // Call activate
        $this->assertNull($optlyObject->getVariation('test_experiment', 'test_user', 42));
    }

    public function testGetVariationAudienceMatch()
    {
        $this->loggerMock->expects($this->any())
            ->method('log')
            ->will($this->returnCallback($this->collectLogsForAssertion));

        $this->assertEquals(
            'control',
            $this->optimizelyObject->getVariation(
                'test_experiment',
                'test_user',
                ['device_type' => 'iPhone', 'location' => 'San Francisco']
            )
        );

        // Verify Logs
        $this->assertContains([Logger::DEBUG, 'User "test_user" is not in the forced variation map.'], $this->collectedLogs);
        $this->assertContains([Logger::DEBUG, 'Assigned bucket 3037 to user "test_user" with bucketing ID "test_user".'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'User "test_user" is in variation control of experiment test_experiment.'], $this->collectedLogs);
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

        $this->loggerMock->expects($this->any())
            ->method('log')
            ->will($this->returnCallback($this->collectLogsForAssertion));

        $this->assertTrue($this->optimizelyObject->setForcedVariation($experimentKey, $userId, $variationKey), 'Set variation for paused experiment should have failed.');
        $this->assertEquals($variationKey, $this->optimizelyObject->getVariation($experimentKey, $userId));

        // Verify Logs
        $this->assertContains([Logger::DEBUG, sprintf('Set variation "%s" for experiment "%s" and user "%s" in the forced variation map.', $variationId, $experimentId, $userId)], $this->collectedLogs);
        $this->assertContains([Logger::DEBUG, sprintf('Variation "%s" is mapped to experiment "%s" and user "%s" in the forced variation map', $variationKey, $experimentKey, $userId)], $this->collectedLogs);
    }

    public function testGetVariationAudienceNoMatch()
    {
        $this->loggerMock->expects($this->any())
            ->method('log')
            ->will($this->returnCallback($this->collectLogsForAssertion));

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
            ->with(Logger::DEBUG, sprintf('Variation "%s" is mapped to experiment "%s" and user "%s" in the forced variation map', $variationKey, $experimentKey, $userId));

        $this->assertTrue($this->optimizelyObject->setForcedVariation($experimentKey, $userId, $variationKey), 'Set variation for paused experiment should have passed.');
        $this->assertEquals($this->optimizelyObject->getVariation($experimentKey, $userId), $variationKey);
    }

    public function testGetVariationUserNotInForcedVariationNotInExperiment()
    {
        // Last check to happen is the audience condition check so we make sure we get to that check since
        // the user is not whitelisted
        $this->loggerMock->expects($this->any())
            ->method('log')
            ->will($this->returnCallback($this->collectLogsForAssertion));

        $this->assertNull($this->optimizelyObject->getVariation('test_experiment', 'test_user'));

        // Verify Logs
        $this->assertContains([Logger::DEBUG, 'User "test_user" is not in the forced variation map.'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'User "test_user" does not meet conditions to be in experiment "test_experiment".'], $this->collectedLogs);
    }

    public function testValidatePreconditionsUserNotInForcedVariationInExperiment()
    {
        $this->loggerMock->expects($this->any())
          ->method('log')
          ->will($this->returnCallback($this->collectLogsForAssertion));

        $this->assertEquals(
            'control',
            $this->optimizelyObject->getVariation(
                'test_experiment',
                'test_user',
                ['device_type' => 'iPhone', 'location' => 'San Francisco']
            )
        );

        // Verify Logs
        $this->assertContains([Logger::DEBUG, 'User "test_user" is not in the forced variation map.'], $this->collectedLogs);
        $this->assertContains([Logger::DEBUG, 'Assigned bucket 3037 to user "test_user" with bucketing ID "test_user".'], $this->collectedLogs);
        $this->assertContains([Logger::INFO, 'User "test_user" is in variation control of experiment test_experiment.'], $this->collectedLogs);
    }

    public function testTrackInvalidOptimizelyObject()
    {
        $optlyObject = new Optimizely('Random datafile', null, new DefaultLogger(Logger::INFO, self::OUTPUT_STREAM));

        // Verify that sendNotifications isn't called
        $this->notificationCenterMock->expects($this->never())
            ->method('sendNotifications');
        $optlyObject->notificationCenter = $this->notificationCenterMock;

        $optlyObject->track('some_event', 'some_user');
        $this->expectOutputRegex('/Datafile has invalid format. Failing "track"./');
    }

    public function testTrackCallsValidateInputs()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile))
            ->setMethods(array('validateInputs'))
            ->getMock();

        $eventKey = 'test_event';
        $userId = 'test_user';
        $inputArray = [
            Optimizely::EVENT_KEY => $eventKey,
            Optimizely::USER_ID => $userId
        ];

        // assert that validateInputs gets called with exactly same keys
        $optimizelyMock->expects($this->once())
            ->method('validateInputs')
            ->with($inputArray)
            ->willReturn(false);

        $this->assertNull($optimizelyMock->track($eventKey, $userId));
    }

    public function testTrackWithInvalidUserID()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        $this->loggerMock->expects($this->exactly(6))
            ->method('log')
            ->with(Logger::ERROR, sprintf('Provided %s is in an invalid format.', Optimizely::USER_ID));

        $optlyObject = new Optimizely(
            $this->datafile,
            new ValidEventDispatcher(),
            $this->loggerMock
        );

        // Verify that sendNotifications isn't called
        $this->notificationCenterMock->expects($this->never())
            ->method('sendNotifications');
        $optlyObject->notificationCenter = $this->notificationCenterMock;

        // Call track
        $this->assertNull($optlyObject->track('purchase', null, $userAttributes, array('revenue' => 42)));
        $this->assertNull($optlyObject->track('purchase', 5, $userAttributes, array('revenue' => 42)));
        $this->assertNull($optlyObject->track('purchase', 5.5, $userAttributes, array('revenue' => 42)));
        $this->assertNull($optlyObject->track('purchase', true, $userAttributes, array('revenue' => 42)));
        $this->assertNull($optlyObject->track('purchase', [], $userAttributes, array('revenue' => 42)));
        $this->assertNull($optlyObject->track('purchase', (object) array(), $userAttributes, array('revenue' => 42)));
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
            $this->datafile,
            new ValidEventDispatcher(),
            $this->loggerMock,
            $errorHandlerMock
        );

        // Verify that sendNotifications isn't called
        $this->notificationCenterMock->expects($this->never())
            ->method('sendNotifications');
        $optlyObject->notificationCenter = $this->notificationCenterMock;

        // Call track
        $this->assertNull($optlyObject->track('purchase', 'test_user', 42));
    }

    public function testTrackInvalidEventTags()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Provided event tags are in an invalid format.');

        $errorHandlerMock = $this->getMockBuilder(NoOpErrorHandler::class)
            ->setMethods(array('handleError'))
            ->getMock();
        $errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidEventTagException('Provided event tags are in an invalid format.'));

        $optlyObject = new Optimizely(
            $this->datafile,
            null,
            $this->loggerMock,
            $errorHandlerMock
        );

        $optlyObject->track('purchase', 'test_user', [], [1=>2]);
    }

    public function testTrackInvalidEventTagsWithDeprecatedRevenueValue()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Provided event tags are in an invalid format.');

        $errorHandlerMock = $this->getMockBuilder(NoOpErrorHandler::class)
            ->setMethods(array('handleError'))
            ->getMock();
        $errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidEventTagException('Provided event tags are in an invalid format.'));

        $optlyObject = new Optimizely(
            $this->datafile,
            null,
            $this->loggerMock,
            $errorHandlerMock
        );

        $optlyObject->track('purchase', 'test_user', [], 42);
    }

    public function testTrackUnknownEventKey()
    {
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::ERROR, 'Event key "unknown_key" is not in datafile.');

        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, 'Not tracking user "test_user" for event "unknown_key".');

        $this->optimizelyObject->track('unknown_key', 'test_user');
    }

    public function testTrackGivenEventKeyWithNoExperiments()
    {
        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'unlinked_event',
                'test_user',
                null,
                null
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($this->optimizelyObject, $this->eventBuilderMock);

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, 'Tracking event "unlinked_event" for user "test_user".');

        $this->optimizelyObject->track('unlinked_event', 'test_user');
    }

    public function testTrackGivenEventKeyWithPausedExperiment()
    {
        $pausedExpEvent = $this->projectConfig->getEvent('purchase');
        // Experiment with ID 7716830585 is paused.
        $pausedExpEvent->setExperimentIds(['7716830585']);

        $config = new \ReflectionProperty(Optimizely::class, '_config');
        $config->setAccessible(true);
        $config->setValue($this->optimizelyObject, $this->projectConfig);

        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'purchase',
                'test_user',
                null,
                null
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($this->optimizelyObject, $this->eventBuilderMock);

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, 'Tracking event "purchase" for user "test_user".');

        $this->optimizelyObject->track('purchase', 'test_user');
    }

    public function testTrackEventDispatchFailure()
    {
        $eventDispatcherMock = $this->getMockBuilder(DefaultEventDispatcher::class)
            ->setMethods(array('dispatchEvent'))
            ->getMock();

        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'purchase',
                'test_user',
                null,
                null
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $eventDispatcher = new \ReflectionProperty(Optimizely::class, '_eventDispatcher');
        $eventDispatcher->setAccessible(true);
        $eventDispatcher->setValue($this->optimizelyObject, $eventDispatcherMock);

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($this->optimizelyObject, $this->eventBuilderMock);

        $eventDispatcherMock->expects($this->once())
            ->method('dispatchEvent')
            ->will($this->throwException(new Exception));

        $this->loggerMock->expects($this->at(2))
            ->method('log')
            ->with(Logger::ERROR, 'Unable to dispatch conversion event. Error ');

        $this->optimizelyObject->track('purchase', 'test_user');
    }

    public function testTrackNoAttributesNoEventValue()
    {
        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfig,
                'purchase',
                'test_user',
                null,
                null
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(
                Logger::INFO,
                'Tracking event "purchase" for user "test_user".'
            );
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(
                Logger::DEBUG,
                'Dispatching conversion event to URL logx.optimizely.com/track with params {"param1":"val1"}.'
            );

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            'purchase',
            'test_user',
            null,
            null,
            new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', [])
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::TRACK,
                $arrayParam
            );
        $optlyObject->notificationCenter = $this->notificationCenterMock;

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
                'test_user',
                null,
                null
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(3))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('Set variation "%s" for experiment "%s" and user "%s" in the forced variation map.', $variationId, $experimentId, $userId));
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(
                Logger::INFO,
                'Tracking event "purchase" for user "test_user".'
            );
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(
                Logger::DEBUG,
                'Dispatching conversion event to URL logx.optimizely.com/track with params {"param1":"val1"}.'
            );

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            'purchase',
            'test_user',
            null,
            null,
            new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', [])
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::TRACK,
                $arrayParam
            );
        $optlyObject->notificationCenter = $this->notificationCenterMock;

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
                'test_user',
                $userAttributes,
                null
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(
                Logger::INFO,
                'Tracking event "purchase" for user "test_user".'
            );
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(
                Logger::DEBUG,
                'Dispatching conversion event to URL logx.optimizely.com/track with params {"param1":"val1"}.'
            );

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            'purchase',
            'test_user',
            $userAttributes,
            null,
            new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', [])
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::TRACK,
                $arrayParam
            );

        $optlyObject->notificationCenter = $this->notificationCenterMock;

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
                'test_user',
                null,
                array('revenue' => 42)
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(
                Logger::INFO,
                'Tracking event "purchase" for user "test_user".'
            );
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(
                Logger::DEBUG,
                'Dispatching conversion event to URL logx.optimizely.com/track with params {"param1":"val1"}.'
            );

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            'purchase',
            'test_user',
            null,
            array('revenue' => 42),
            new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', [])
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::TRACK,
                $arrayParam
            );

        $optlyObject->notificationCenter = $this->notificationCenterMock;

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
                'test_user',
                null,
                array('revenue' => '4200')
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'Tracking event "purchase" for user "test_user".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'Dispatching conversion event to URL logx.optimizely.com/track with params {"param1":"val1"}.');

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            'purchase',
            'test_user',
            null,
            array('revenue' => '4200'),
            new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', [])
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::TRACK,
                $arrayParam
            );

        $optlyObject->notificationCenter = $this->notificationCenterMock;

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call track
        $optlyObject->track('purchase', 'test_user', null, array('revenue' => '4200'));
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
                'test_user',
                $userAttributes,
                array('revenue' => 42)
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(
                Logger::INFO,
                'Tracking event "purchase" for user "test_user".'
            );
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(
                Logger::DEBUG,
                'Dispatching conversion event to URL logx.optimizely.com/track with params {"param1":"val1"}.'
            );

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            'purchase',
            'test_user',
            $userAttributes,
            array('revenue' => 42),
            new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', [])
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::TRACK,
                $arrayParam
            );

        $optlyObject->notificationCenter = $this->notificationCenterMock;

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call track
        $optlyObject->track('purchase', 'test_user', $userAttributes, array('revenue' => 42));
    }

    public function testTrackWithAttributesTypedAudienceMatch()
    {
        $userAttributes = [
            'house' => 'Welcome to Slytherin!'
        ];

        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfigForTypedAudience,
                'item_bought',
                'test_user',
                $userAttributes,
                array('revenue' => 42)
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $optlyObject = new Optimizely($this->typedAudiencesDataFile, new ValidEventDispatcher(), $this->loggerMock);

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            'item_bought',
            'test_user',
            $userAttributes,
            array('revenue' => 42),
            new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', [])
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::TRACK,
                $arrayParam
            );
        $optlyObject->notificationCenter = $this->notificationCenterMock;

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Should be included via substring match string audience with id '3988293898'
        $optlyObject->track('item_bought', 'test_user', $userAttributes, array('revenue' => 42));
    }

    public function testTrackWithAttributesTypedAudienceMismatch()
    {
        $userAttributes = [
            'house' => 'Hufflepuff!'
        ];

        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfigForTypedAudience,
                'item_bought',
                'test_user',
                $userAttributes,
                array('revenue' => 42)
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $optlyObject = new Optimizely($this->typedAudiencesDataFile, new ValidEventDispatcher(), $this->loggerMock);

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            'item_bought',
            'test_user',
            $userAttributes,
            array('revenue' => 42),
            new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', [])
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::TRACK,
                $arrayParam
            );
        $optlyObject->notificationCenter = $this->notificationCenterMock;

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call track
        $optlyObject->track('item_bought', 'test_user', $userAttributes, array('revenue' => 42));
    }

    public function testTrackWithAttributesComplexAudienceMatch()
    {
        $userAttributes = [
            'house' => 'Gryffindor',
            'should_do_it' => true
        ];

        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfigForTypedAudience,
                'user_signed_up',
                'test_user',
                $userAttributes,
                array('revenue' => 42)
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $optlyObject = new Optimizely($this->typedAudiencesDataFile, new ValidEventDispatcher(), $this->loggerMock);

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            'user_signed_up',
            'test_user',
            $userAttributes,
            array('revenue' => 42),
            new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', [])
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::TRACK,
                $arrayParam
            );
        $optlyObject->notificationCenter = $this->notificationCenterMock;

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Should be included via exact match string audience with id '3468206642', and
        // exact match boolean audience with id '3468206643'
        $optlyObject->track('user_signed_up', 'test_user', $userAttributes, array('revenue' => 42));
    }

    public function testTrackWithAttributesComplexAudienceMismatch()
    {
        $userAttributes = [
            'house' => 'Gryffindor',
            'should_do_it' => false
        ];

        $this->eventBuilderMock->expects($this->once())
            ->method('createConversionEvent')
            ->with(
                $this->projectConfigForTypedAudience,
                'user_signed_up',
                'test_user',
                $userAttributes,
                array('revenue' => 42)
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $optlyObject = new Optimizely($this->typedAudiencesDataFile, new ValidEventDispatcher(), $this->loggerMock);

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            'user_signed_up',
            'test_user',
            $userAttributes,
            array('revenue' => 42),
            new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', [])
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::TRACK,
                $arrayParam
            );
        $optlyObject->notificationCenter = $this->notificationCenterMock;

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Should be excluded - exact match boolean audience with id '3468206643' does not match,
        // so the overall conditions fail
        $optlyObject->track('user_signed_up', 'test_user', $userAttributes, array('revenue' => 42));
    }

    public function testTrackWithEmptyUserID()
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
                '',
                $userAttributes,
                array('revenue' => 42)
            )
            ->willReturn(new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', []));

        $callIndex = 0;
        $this->loggerMock->expects($this->exactly(2))
            ->method('log');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(
                Logger::INFO,
                'Tracking event "purchase" for user "".'
            );
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(
                Logger::DEBUG,
                'Dispatching conversion event to URL logx.optimizely.com/track with params {"param1":"val1"}.'
            );

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            'purchase',
            '',
            $userAttributes,
            array('revenue' => 42),
            new LogEvent('logx.optimizely.com/track', ['param1' => 'val1'], 'POST', [])
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::TRACK,
                $arrayParam
            );

        $optlyObject->notificationCenter = $this->notificationCenterMock;

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call track
        $optlyObject->track('purchase', '', $userAttributes, array('revenue' => 42));
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

    public function testSetForcedVariationWithEmptyUserID()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'San Francisco'
        ];

        $variationKey = $optlyObject->getVariation('test_experiment', '', $userAttributes);
        $this->assertEquals('variation', $variationKey, sprintf('Invalid variation "%s" for baseline check.', $variationKey));

        // valid experiment, valid variation, empty user ID --> the user should be bucketed to the specified forced variation
        $this->assertTrue($optlyObject->setForcedVariation('test_experiment', '', 'variation'), 'Set variation for valid conditions should have succeeded.');
        $variationKey = $optlyObject->getVariation('test_experiment', '', $userAttributes);
        $this->assertEquals('variation', $variationKey, sprintf('Invalid variation "%s" for valid conditions.', $variationKey));
    }

    public function testSetForcedVariationWithInvalidUserIDs()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $this->loggerMock->expects($this->exactly(6))
            ->method('log')
            ->with(Logger::ERROR, sprintf('Provided %s is in an invalid format.', Optimizely::USER_ID));

        $this->assertFalse($optlyObject->setForcedVariation('test_experiment', null, 'control'));
        $this->assertFalse($optlyObject->setForcedVariation('test_experiment', 5, 'control'));
        $this->assertFalse($optlyObject->setForcedVariation('test_experiment', 5.5, 'control'));
        $this->assertFalse($optlyObject->setForcedVariation('test_experiment', false, 'control'));
        $this->assertFalse($optlyObject->setForcedVariation('test_experiment', [], 'control'));
        $this->assertFalse($optlyObject->setForcedVariation('test_experiment', (object) array(), 'control'));
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

    public function testGetForcedVariationWithEmptyUserID()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $this->assertTrue($optlyObject->setForcedVariation('test_experiment', '', 'variation'), 'Set variation to "variation" failed.');
        // call getForcedVariation with valid experiment key and empty user ID
        $forcedVariationKey = $optlyObject->getForcedVariation('test_experiment', '');
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

        $this->loggerMock->expects($this->exactly(6))
            ->method('log')
            ->with(Logger::ERROR, sprintf('Provided %s is in an invalid format.', Optimizely::USER_ID));

        $this->assertNull($optlyObject->getForcedVariation('test_experiment', null));
        $this->assertNull($optlyObject->getForcedVariation('test_experiment', 5));
        $this->assertNull($optlyObject->getForcedVariation('test_experiment', 5.5));
        $this->assertNull($optlyObject->getForcedVariation('test_experiment', false));
        $this->assertNull($optlyObject->getForcedVariation('test_experiment', []));
        $this->assertNull($optlyObject->getForcedVariation('test_experiment', (object) array()));
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
            ->with(Logger::ERROR, sprintf('Provided %s is in an invalid format.', Optimizely::USER_ID));

        $optlyObject = new Optimizely(DATAFILE, new ValidEventDispatcher(), $this->loggerMock);

        $optlyObject->setForcedVariation($invalidExperimentKey, $userId, $variationKey);
        $optlyObject->setForcedVariation($experimentKey, $userId, null);
        $optlyObject->setForcedVariation($experimentKey, $userId, $invalidVariationKey);
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
            ->with(Logger::ERROR, sprintf('Provided %s is in an invalid format.', Optimizely::USER_ID));

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
            "\$opt_bucketing_id" => $this->testBucketingIdVariation
        ];

        $optlyObject = new Optimizely(DATAFILE, new ValidEventDispatcher(), $this->loggerMock);

        // confirm that a valid variation is bucketed without the bucketing ID
        $variationKey = $optlyObject->getVariation($this->experimentKey, $this->userId, $userAttributes);
        $this->assertEquals($this->variationKeyControl, $variationKey, sprintf('Invalid variation key "%s" for getVariation.', $variationKey));

        // confirm that invalid audience returns null
        $variationKey = $optlyObject->getVariation($this->experimentKey, $this->userId);
        $this->assertNull($variationKey, sprintf('Invalid variation key "%s" for getVariation with bucketing ID "%s".', $variationKey, $this->testBucketingIdControl));

        // confirm that a valid variation is bucketed with the bucketing ID
        $variationKey = $optlyObject->getVariation($this->experimentKey, $this->userId, $userAttributesWithBucketingId);
        $this->assertEquals($this->variationKeyVariation, $variationKey, sprintf('Invalid variation key "%s" for getVariation with bucketing ID "%s".', $variationKey, $this->testBucketingIdVariation));

        // confirm that invalid experiment with the bucketing ID returns null
        $variationKey = $optlyObject->getVariation("invalidExperimentKey", $this->userId, $userAttributesWithBucketingId);
        $this->assertNull($variationKey, sprintf('Invalid variation key "%s" for getVariation with bucketing ID "%s".', $variationKey, $this->testBucketingIdControl));
    }

    public function testIsFeatureEnabledGivenInvalidDataFile()
    {
        $optlyObject = new Optimizely('Random datafile', null, new DefaultLogger(Logger::INFO, self::OUTPUT_STREAM));

        $this->expectOutputRegex("/Datafile has invalid format. Failing 'isFeatureEnabled'./");
        $optlyObject->isFeatureEnabled("boolean_feature", "user_id");
    }

    public function testIsFeatureEnabledCallsValidateInputs()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile))
            ->setMethods(array('validateInputs'))
            ->getMock();

        $featureKey = 'boolean_feature';
        $userId = null;
        $inputArray = [
            'Feature Flag Key' => $featureKey,
            'User ID' => $userId
        ];

        // assert that validateInputs gets called with exactly same keys
        $optimizelyMock->expects($this->once())
            ->method('validateInputs')
            ->with($inputArray)
            ->willReturn(false);

        $this->assertFalse($optimizelyMock->isFeatureEnabled($featureKey, $userId));
    }

    public function testIsFeatureEnabledWithInvalidUserID()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $this->loggerMock->expects($this->exactly(6))
            ->method('log')
            ->with(Logger::ERROR, sprintf('Provided %s is in an invalid format.', Optimizely::USER_ID));

        $this->assertFalse($optlyObject->isFeatureEnabled('boolean_feature', null));
        $this->assertFalse($optlyObject->isFeatureEnabled('boolean_feature', 5));
        $this->assertFalse($optlyObject->isFeatureEnabled('boolean_feature', 5.5));
        $this->assertFalse($optlyObject->isFeatureEnabled('boolean_feature', false));
        $this->assertFalse($optlyObject->isFeatureEnabled('boolean_feature', []));
        $this->assertFalse($optlyObject->isFeatureEnabled('boolean_feature', (object) array()));
    }

    public function testIsFeatureEnabledGivenFeatureFlagNotFound()
    {
        $feature_key = "abcd"; // Any string that is not a feature flag key in the data file

        //should return false and log a message when no feature flag found against a valid feature key
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::ERROR, "FeatureFlag Key \"{$feature_key}\" is not in datafile.");
        $this->assertFalse($this->optimizelyObject->isFeatureEnabled($feature_key, "user_id"));
    }

    public function testIsFeatureEnabledGivenInvalidFeatureFlag()
    {
        // Create local config copy for this method to add error
        $projectConfig = new ProjectConfig($this->datafile, $this->loggerMock, new NoOpErrorHandler());
        $optimizelyObj = new Optimizely($this->datafile);

        $config = new \ReflectionProperty(Optimizely::class, '_config');
        $config->setAccessible(true);
        $config->setValue($optimizelyObj, $projectConfig);

        $featureFlag = $projectConfig->getFeatureFlagFromKey('mutex_group_feature');
        // Add such an experiment to the list of experiment ids, that does not belong to the same mutex group
        $experimentIds = $featureFlag->getExperimentIds();
        $experimentIds [] = '122241';
        $featureFlag->setExperimentIds($experimentIds);

        //should return false when feature flag is invalid
        $this->assertFalse($optimizelyObj->isFeatureEnabled('mutex_group_feature', "user_id"));
    }

    public function testIsFeatureEnabledGivenGetVariationForFeatureReturnsRolloutDecision()
    {
        // should return false when no variation is returned for user
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($optimizelyMock, $decisionServiceMock);

        // mock getVariationForFeature to return rolloutDecision
        $expectedDecision = new FeatureDecision(
            null,
            null,
            FeatureDecision::DECISION_SOURCE_ROLLOUT
        );

        // mock getVariationForFeature to return null
        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expectedDecision));

        // assert that impression event is not sent
        $optimizelyMock->expects($this->never())
            ->method('sendImpressionEvent');

        $optimizelyMock->notificationCenter = $this->notificationCenterMock;

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "Feature Flag 'double_single_variable_feature' is not enabled for user 'user_id'.");

        $this->assertFalse($optimizelyMock->isFeatureEnabled('double_single_variable_feature', 'user_id'));
    }

    public function testIsFeatureEnabledCallsDecisionListenerWhenUserNotInExperimentOrRollout()
    {
        // should return false when no variation is returned for user
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($optimizelyMock, $decisionServiceMock);

        // mock getVariationForFeature to return rolloutDecision
        $expectedDecision = new FeatureDecision(
            null,
            null,
            FeatureDecision::DECISION_SOURCE_ROLLOUT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expectedDecision));


        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            DecisionInfoTypes::FEATURE,
            'user_id',
            [],
            (object) array(
                'featureKey'=>'double_single_variable_feature',
                'featureEnabled'=> false,
                'source'=> 'ROLLOUT',
                'sourceExperimentKey'=> null,
                'sourceVariationKey'=> null
            )
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::DECISION,
                $arrayParam
            );

        $optimizelyMock->notificationCenter = $this->notificationCenterMock;

        $optimizelyMock->isFeatureEnabled('double_single_variable_feature', 'user_id');
    }

    public function testIsFeatureEnabledGivenFeatureExperimentAndFeatureEnabledIsTrue()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($optimizelyMock, $decisionServiceMock);

        // Mock getVariationForFeature to return a valid decision with experiment and variation keys
        $experiment = $this->projectConfig->getExperimentFromKey('test_experiment_double_feature');
        $variation = $this->projectConfig->getVariationFromKey('test_experiment_double_feature', 'control');

        // assert that featureEnabled for $variation is true
        $this->assertTrue($variation->getFeatureEnabled());

        $expected_decision = new FeatureDecision(
            $experiment,
            $variation,
            FeatureDecision::DECISION_SOURCE_EXPERIMENT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expected_decision));

        // assert that sendImpressionEvent is called with expected params
        $optimizelyMock->expects($this->exactly(1))
            ->method('sendImpressionEvent')
            ->with('test_experiment_double_feature', 'control', 'user_id', []);

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "Feature Flag 'double_single_variable_feature' is enabled for user 'user_id'.");

        $this->assertTrue($optimizelyMock->isFeatureEnabled('double_single_variable_feature', 'user_id', []));
    }

    public function testIsFeatureEnabledCallsDecisionListenerGivenFeatureExperimentAndFeatureEnabledIsTrue()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($optimizelyMock, $decisionServiceMock);

        // Mock getVariationForFeature to return a valid decision with experiment and variation keys
        $experiment = $this->projectConfig->getExperimentFromKey('test_experiment_double_feature');
        $variation = $this->projectConfig->getVariationFromKey('test_experiment_double_feature', 'control');

        // assert that featureEnabled for $variation is true
        $this->assertTrue($variation->getFeatureEnabled());

        $expected_decision = new FeatureDecision(
            $experiment,
            $variation,
            FeatureDecision::DECISION_SOURCE_EXPERIMENT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expected_decision));

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            DecisionInfoTypes::FEATURE,
            'user_id',
            ['device_type' => 'iPhone'],
            (object) array(
                'featureKey'=>'double_single_variable_feature',
                'featureEnabled'=> true,
                'source'=> 'EXPERIMENT',
                'sourceExperimentKey'=> 'test_experiment_double_feature',
                'sourceVariationKey'=> 'control'
            )
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::DECISION,
                $arrayParam
            );

        $optimizelyMock->notificationCenter = $this->notificationCenterMock;

        $optimizelyMock->isFeatureEnabled('double_single_variable_feature', 'user_id', ['device_type' => 'iPhone']);
    }

    public function testIsFeatureEnabledGivenFeatureExperimentAndFeatureEnabledIsFalse()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($optimizelyMock, $decisionServiceMock);

        // Mock getVariationForFeature to return a valid decision with experiment and variation keys
        $experiment = $this->projectConfig->getExperimentFromKey('test_experiment_double_feature');
        $variation = $this->projectConfig->getVariationFromKey('test_experiment_double_feature', 'variation');

        // assert that featureEnabled for $variation is false
        $this->assertFalse($variation->getFeatureEnabled());

        $expected_decision = new FeatureDecision(
            $experiment,
            $variation,
            FeatureDecision::DECISION_SOURCE_EXPERIMENT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expected_decision));

        $optimizelyMock->expects($this->exactly(1))
            ->method('sendImpressionEvent')
            ->with('test_experiment_double_feature', 'variation', 'user_id', []);

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "Feature Flag 'double_single_variable_feature' is not enabled for user 'user_id'.");

        $this->assertFalse($optimizelyMock->isFeatureEnabled('double_single_variable_feature', 'user_id', []));
    }

    public function testIsFeatureEnabledCallsDecisionListenerGivenFeatureExperimentAndFeatureEnabledIsFalse()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($optimizelyMock, $decisionServiceMock);

        // Mock getVariationForFeature to return a valid decision with experiment and variation keys
        $experiment = $this->projectConfig->getExperimentFromKey('test_experiment_double_feature');
        $variation = $this->projectConfig->getVariationFromKey('test_experiment_double_feature', 'variation');

        // assert that featureEnabled for $variation is false
        $this->assertFalse($variation->getFeatureEnabled());

        $expected_decision = new FeatureDecision(
            $experiment,
            $variation,
            FeatureDecision::DECISION_SOURCE_EXPERIMENT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expected_decision));

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            DecisionInfoTypes::FEATURE,
            'user_id',
            [],
            (object) array(
                'featureKey'=>'double_single_variable_feature',
                'featureEnabled'=> false,
                'source'=> 'EXPERIMENT',
                'sourceExperimentKey'=> 'test_experiment_double_feature',
                'sourceVariationKey'=> 'variation'
            )
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::DECISION,
                $arrayParam
            );

        $optimizelyMock->notificationCenter = $this->notificationCenterMock;


        $optimizelyMock->isFeatureEnabled('double_single_variable_feature', 'user_id');
    }

    public function testIsFeatureEnabledGivenFeatureRolloutAndFeatureEnabledIsTrue()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($optimizelyMock, $decisionServiceMock);

        // Mock getVariationForFeature to return a valid decision with experiment and variation keys
        $rollout = $this->projectConfig->getRolloutFromId('166660');
        $experiment = $rollout->getExperiments()[0];
        $variation = $experiment->getVariations()[0];

        // assert variation's 'featureEnabled' is set to true
        $this->assertTrue($variation->getFeatureEnabled());

        $expected_decision = new FeatureDecision(
            $experiment,
            $variation,
            FeatureDecision::DECISION_SOURCE_ROLLOUT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expected_decision));

        // assert that sendImpressionEvent is not called
        $optimizelyMock->expects($this->never())
            ->method('sendImpressionEvent');

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(
                Logger::INFO,
                "The user 'user_id' is not being experimented on Feature Flag 'boolean_single_variable_feature'."
            );

        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, "Feature Flag 'boolean_single_variable_feature' is enabled for user 'user_id'.");

        $this->assertTrue($optimizelyMock->isFeatureEnabled('boolean_single_variable_feature', 'user_id', []));
    }

    public function testIsFeatureEnabledCallsDecisionListenerGivenFeatureRolloutAndFeatureEnabledIsTrue()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($optimizelyMock, $decisionServiceMock);

        // Mock getVariationForFeature to return a valid decision with experiment and variation keys
        $rollout = $this->projectConfig->getRolloutFromId('166660');
        $experiment = $rollout->getExperiments()[0];
        $variation = $experiment->getVariations()[0];

        // assert variation's 'featureEnabled' is set to true
        $this->assertTrue($variation->getFeatureEnabled());

        $expected_decision = new FeatureDecision(
            $experiment,
            $variation,
            FeatureDecision::DECISION_SOURCE_ROLLOUT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expected_decision));

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            DecisionInfoTypes::FEATURE,
            'user_id',
            ['device_type' => 'iPhone'],
            (object) array(
                'featureKey'=>'boolean_single_variable_feature',
                'featureEnabled'=> true,
                'source'=> 'ROLLOUT',
                'sourceExperimentKey'=> null,
                'sourceVariationKey'=> null
            )
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::DECISION,
                $arrayParam
            );

        $optimizelyMock->notificationCenter = $this->notificationCenterMock;

        $optimizelyMock->isFeatureEnabled('boolean_single_variable_feature', 'user_id', ['device_type' => 'iPhone']);
    }

    public function testIsFeatureEnabledGivenFeatureRolloutAndFeatureEnabledIsFalse()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($optimizelyMock, $decisionServiceMock);

        // Mock getVariationForFeature to return a valid decision with experiment and variation keys
        $rollout = $this->projectConfig->getRolloutFromId('166660');
        $experiment = $rollout->getExperiments()[0];
        $variation = $experiment->getVariations()[0];
        $variation->setFeatureEnabled(false);

        // assert variation's 'featureEnabled' is set to false
        $this->assertFalse($variation->getFeatureEnabled());

        $expected_decision = new FeatureDecision(
            $experiment,
            $variation,
            FeatureDecision::DECISION_SOURCE_ROLLOUT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expected_decision));

        // assert that sendImpressionEvent is not called
        $optimizelyMock->expects($this->never())
            ->method('sendImpressionEvent');

        // confirm log messages seen
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The user 'user_id' is not being experimented on Feature Flag 'boolean_single_variable_feature'.");

        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::INFO, "Feature Flag 'boolean_single_variable_feature' is not enabled for user 'user_id'.");

        $this->assertFalse($optimizelyMock->isFeatureEnabled('boolean_single_variable_feature', 'user_id', []));
    }

    public function testIsFeatureEnabledCallsDecisionListenerGivenFeatureRolloutAndFeatureEnabledIsFalse()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($optimizelyMock, $decisionServiceMock);

        // Mock getVariationForFeature to return a valid decision with experiment and variation keys
        $rollout = $this->projectConfig->getRolloutFromId('166660');
        $experiment = $rollout->getExperiments()[0];
        $variation = $experiment->getVariations()[0];
        $variation->setFeatureEnabled(false);

        // assert variation's 'featureEnabled' is set to false
        $this->assertFalse($variation->getFeatureEnabled());

        $expected_decision = new FeatureDecision(
            $experiment,
            $variation,
            FeatureDecision::DECISION_SOURCE_ROLLOUT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expected_decision));

        // Verify that sendNotifications is called with expected params
        $arrayParam = array(
            DecisionInfoTypes::FEATURE,
            'user_id',
            [],
            (object) array(
                'featureKey'=>'boolean_single_variable_feature',
                'featureEnabled'=> false,
                'source'=> 'ROLLOUT',
                'sourceExperimentKey'=> null,
                'sourceVariationKey'=> null
            )
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::DECISION,
                $arrayParam
            );

        $optimizelyMock->notificationCenter = $this->notificationCenterMock;

        $optimizelyMock->isFeatureEnabled('boolean_single_variable_feature', 'user_id', []);
    }

    public function testIsFeatureEnabledGivenFeatureRolloutTypedAudienceMatch()
    {
        $userAttributes = [
            'favorite_ice_cream' => 'chocolate'
        ];

        // Should be included via exists match audience with id '3988293899'
        $this->assertTrue(
            $this->optimizelyTypedAudienceObject->isFeatureEnabled('feat', 'test_user', $userAttributes)
        );

        $userAttributes = [
            'lasers' => -3
        ];

        // Should be included via less-than match audience with id '3468206644'
        $this->assertTrue(
            $this->optimizelyTypedAudienceObject->isFeatureEnabled('feat', 'test_user', $userAttributes)
        );
    }

    public function testIsFeatureEnabledGivenFeatureRolloutTypedAudienceMismatch()
    {
        $userAttributes = [];

        $this->assertFalse(
            $this->optimizelyTypedAudienceObject->isFeatureEnabled('feat', 'test_user', $userAttributes)
        );
    }

    public function testIsFeatureEnabledGivenFeatureRolloutComplexAudienceMatch()
    {
        $userAttributes = [
            'house' => '...Slytherinnn...sss.',
            'favorite_ice_cream' => 'matcha'
        ];

        // Should be included via substring match string audience with id '3988293898', and
        // exists audience with id '3988293899'
        $this->assertTrue(
            $this->optimizelyTypedAudienceObject->isFeatureEnabled('feat2', 'test_user', $userAttributes)
        );
    }

    public function testIsFeatureEnabledGivenFeatureRolloutComplexAudienceMismatch()
    {
        $userAttributes = [
            'house' => 'Lannister'
        ];

        // Should be excluded - substring match string audience with id '3988293898' does not match,
        // and no other audience matches either
        $this->assertFalse(
            $this->optimizelyTypedAudienceObject->isFeatureEnabled('feat2', 'test_user', $userAttributes)
        );
    }

    public function testIsFeatureEnabledWithEmptyUserID()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($optimizelyMock, $decisionServiceMock);

        // Mock getVariationForFeature to return a valid decision with experiment and variation keys
        $experiment = $this->projectConfig->getExperimentFromKey('test_experiment_double_feature');
        $variation = $this->projectConfig->getVariationFromKey('test_experiment_double_feature', 'control');

        $expected_decision = new FeatureDecision(
            $experiment,
            $variation,
            FeatureDecision::DECISION_SOURCE_EXPERIMENT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expected_decision));

        // assert that sendImpressionEvent is called with expected params
        $optimizelyMock->expects($this->exactly(1))
            ->method('sendImpressionEvent')
            ->with('test_experiment_double_feature', 'control', '', []);

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "Feature Flag 'double_single_variable_feature' is enabled for user ''.");

        $this->assertTrue($optimizelyMock->isFeatureEnabled('double_single_variable_feature', '', []));
    }

    public function testGetEnabledFeaturesGivenInvalidDataFile()
    {
        $optlyObject = new Optimizely('Random datafile', null, new DefaultLogger(Logger::INFO, self::OUTPUT_STREAM));

        $this->expectOutputRegex("/Datafile has invalid format. Failing 'getEnabledFeatures'./");

        $this->assertEmpty($optlyObject->getEnabledFeatures("user_id", []));
    }

    public function testGetEnabledFeaturesWithInvalidUserID()
    {
        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $this->loggerMock->expects($this->exactly(6))
            ->method('log')
            ->with(Logger::ERROR, sprintf('Provided %s is in an invalid format.', Optimizely::USER_ID));

        $this->assertEmpty($optlyObject->getEnabledFeatures(null));
        $this->assertEmpty($optlyObject->getEnabledFeatures(5));
        $this->assertEmpty($optlyObject->getEnabledFeatures(5.5));
        $this->assertEmpty($optlyObject->getEnabledFeatures(false));
        $this->assertEmpty($optlyObject->getEnabledFeatures([]));
        $this->assertEmpty($optlyObject->getEnabledFeatures((object) array()));
    }

    public function testGetEnabledFeaturesGivenNoFeatureIsEnabledForUser()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile))
            ->setMethods(array('isFeatureEnabled'))
            ->getMock();

        // Mock isFeatureEnabled to return false for all calls
        $optimizelyMock->expects($this->exactly(8))
            ->method('isFeatureEnabled')
            ->will($this->returnValue(false));

        $this->assertEmpty($optimizelyMock->getEnabledFeatures("user_id", []));
    }

    public function testGetEnabledFeaturesGivenFeaturesAreEnabledForUser()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile))
            ->setMethods(array('isFeatureEnabled'))
            ->getMock();

        $map = [
            ['boolean_feature','user_id', [], true],
            ['double_single_variable_feature','user_id', [], false],
            ['integer_single_variable_feature','user_id', [], false],
            ['boolean_single_variable_feature','user_id', [], true],
            ['string_single_variable_feature','user_id', [], false],
            ['multi_variate_feature','user_id', [], false],
            ['mutex_group_feature','user_id', [], false],
            ['empty_feature','user_id', [], true],
        ];

        // Mock isFeatureEnabled to return specific values
        $optimizelyMock->expects($this->exactly(8))
            ->method('isFeatureEnabled')
            ->will($this->returnValueMap($map));

        $this->assertEquals(
            ['boolean_feature', 'boolean_single_variable_feature', 'empty_feature'],
            $optimizelyMock->getEnabledFeatures("user_id", [])
        );
    }

    public function testGetEnabledFeaturesGivenFeaturesAreEnabledForEmptyUserID()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile))
            ->setMethods(array('isFeatureEnabled'))
            ->getMock();

        $map = [
            ['boolean_feature','', [], true],
            ['double_single_variable_feature','', [], true]
        ];

        // Mock isFeatureEnabled to return specific values
        $optimizelyMock->expects($this->exactly(8))
            ->method('isFeatureEnabled')
            ->will($this->returnValueMap($map));

        $this->assertEquals(
            ['boolean_feature', 'double_single_variable_feature'],
            $optimizelyMock->getEnabledFeatures("", [])
        );
    }

    public function testGetEnabledFeaturesWithUserAttributes()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile))
            ->setMethods(array('isFeatureEnabled'))
            ->getMock();

        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        $map = [
            ['boolean_feature','user_id', $userAttributes, false],
            ['double_single_variable_feature','user_id', $userAttributes, false],
            ['integer_single_variable_feature','user_id', $userAttributes, false],
            ['boolean_single_variable_feature','user_id', $userAttributes, false],
            ['string_single_variable_feature','user_id', $userAttributes, false],
            ['multi_variate_feature','user_id', $userAttributes, false],
            ['mutex_group_feature','user_id', $userAttributes, false],
            ['empty_feature','user_id', $userAttributes, true],
        ];

        // Assert that isFeatureEnabled is called with the same attributes and mock to return value map
        $optimizelyMock->expects($this->exactly(8))
            ->method('isFeatureEnabled')
            ->with()
            ->will($this->returnValueMap($map));

        $this->assertEquals(
            ['empty_feature'],
            $optimizelyMock->getEnabledFeatures("user_id", $userAttributes)
        );
    }

    public function testGetEnabledFeaturesCallsDecisionListenerForAllFeatures()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile))
            ->setMethods(array('sendImpressionEvent'))
            ->getMock();

        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($optimizelyMock, $decisionServiceMock);

        // Mock getVariationForFeature to return a valid decision with experiment and variation keys
        $rollout = $this->projectConfig->getRolloutFromId('166660');
        $experiment = $rollout->getExperiments()[0];
        $enabledFeatureVariation = $experiment->getVariations()[0];

        $disabledFeatureExperiment = $this->projectConfig->getExperimentFromKey(
            'test_experiment_double_feature'
        );
        $disabledFeatureVariation = $disabledFeatureExperiment->getVariations()[1];

        $decision1 = new FeatureDecision(
            $experiment,
            $enabledFeatureVariation,
            FeatureDecision::DECISION_SOURCE_EXPERIMENT
        );
        $decision2 = new FeatureDecision(
            $disabledFeatureExperiment,
            $disabledFeatureVariation,
            FeatureDecision::DECISION_SOURCE_EXPERIMENT
        );
        $decision3 = new FeatureDecision(
            $experiment,
            $enabledFeatureVariation,
            FeatureDecision::DECISION_SOURCE_ROLLOUT
        );
        $decision4 = new FeatureDecision(
            $disabledFeatureExperiment,
            $disabledFeatureVariation,
            FeatureDecision::DECISION_SOURCE_ROLLOUT
        );
        $decision5 = new FeatureDecision(
            $experiment,
            $enabledFeatureVariation,
            FeatureDecision::DECISION_SOURCE_EXPERIMENT
        );
        $decision6 = new FeatureDecision(
            $disabledFeatureExperiment,
            $disabledFeatureVariation,
            FeatureDecision::DECISION_SOURCE_EXPERIMENT
        );
        $decision7 = new FeatureDecision(
            $experiment,
            $enabledFeatureVariation,
            FeatureDecision::DECISION_SOURCE_ROLLOUT
        );
        $decision8 = new FeatureDecision(
            $disabledFeatureExperiment,
            $disabledFeatureVariation,
            FeatureDecision::DECISION_SOURCE_ROLLOUT
        );

        $decisionServiceMock->expects($this->exactly(8))
            ->method('getVariationForFeature')
            ->will($this->onConsecutiveCalls(
                $decision1,
                $decision2,
                $decision3,
                $decision4,
                $decision5,
                $decision6,
                $decision7,
                $decision8
            ));

        $optimizelyMock->notificationCenter = $this->notificationCenterMock;

        $this->notificationCenterMock->expects($this->at(0))
            ->method('sendNotifications')
            ->with(
                NotificationType::DECISION,
                array(
                    DecisionInfoTypes::FEATURE,
                    'user_id',
                    [],
                    (object) array(
                        'featureKey'=>'boolean_feature',
                        'featureEnabled'=> true,
                        'source'=> 'EXPERIMENT',
                        'sourceExperimentKey'=> 'rollout_1_exp_1',
                        'sourceVariationKey'=> '177771'
                    )
                )
            );

        $this->notificationCenterMock->expects($this->at(1))
            ->method('sendNotifications')
            ->with(
                NotificationType::DECISION,
                array(
                    DecisionInfoTypes::FEATURE,
                    'user_id',
                    [],
                    (object) array(
                        'featureKey'=>'double_single_variable_feature',
                        'featureEnabled'=> false,
                        'source'=> 'EXPERIMENT',
                        'sourceExperimentKey'=> 'test_experiment_double_feature',
                        'sourceVariationKey'=> 'variation'
                    )
                )
            );

        $this->notificationCenterMock->expects($this->at(2))
            ->method('sendNotifications')
            ->with(
                NotificationType::DECISION,
                array(
                    DecisionInfoTypes::FEATURE,
                    'user_id',
                    [],
                    (object) array(
                        'featureKey'=>'integer_single_variable_feature',
                        'featureEnabled'=> true,
                        'source'=> 'ROLLOUT',
                        'sourceExperimentKey'=> null,
                        'sourceVariationKey'=> null
                    )
                )
            );

        $this->notificationCenterMock->expects($this->at(3))
            ->method('sendNotifications')
            ->with(
                NotificationType::DECISION,
                array(
                    DecisionInfoTypes::FEATURE,
                    'user_id',
                    [],
                    (object) array(
                        'featureKey'=>'boolean_single_variable_feature',
                        'featureEnabled'=> false,
                        'source'=> 'ROLLOUT',
                        'sourceExperimentKey'=> null,
                        'sourceVariationKey'=> null
                    )
                )
            );

        $this->notificationCenterMock->expects($this->at(4))
            ->method('sendNotifications')
            ->with(
                NotificationType::DECISION,
                array(
                    DecisionInfoTypes::FEATURE,
                    'user_id',
                    [],
                    (object) array(
                        'featureKey'=>'string_single_variable_feature',
                        'featureEnabled'=> true,
                        'source'=> 'EXPERIMENT',
                        'sourceExperimentKey'=> 'rollout_1_exp_1',
                        'sourceVariationKey'=> '177771'
                    )
                )
            );

        $this->notificationCenterMock->expects($this->at(5))
            ->method('sendNotifications')
            ->with(
                NotificationType::DECISION,
                array(
                    DecisionInfoTypes::FEATURE,
                    'user_id',
                    [],
                    (object) array(
                        'featureKey'=>'multi_variate_feature',
                        'featureEnabled'=> false,
                        'source'=> 'EXPERIMENT',
                        'sourceExperimentKey'=> 'test_experiment_double_feature',
                        'sourceVariationKey'=> 'variation'
                    )
                )
            );

        $this->notificationCenterMock->expects($this->at(6))
            ->method('sendNotifications')
            ->with(
                NotificationType::DECISION,
                array(
                    DecisionInfoTypes::FEATURE,
                    'user_id',
                    [],
                    (object) array(
                        'featureKey'=>'mutex_group_feature',
                        'featureEnabled'=> true,
                        'source'=> 'ROLLOUT',
                        'sourceExperimentKey'=> null,
                        'sourceVariationKey'=> null
                    )
                )
            );

        $this->notificationCenterMock->expects($this->at(7))
            ->method('sendNotifications')
            ->with(
                NotificationType::DECISION,
                array(
                    DecisionInfoTypes::FEATURE,
                    'user_id',
                    [],
                    (object) array(
                        'featureKey'=>'empty_feature',
                        'featureEnabled'=> false,
                        'source'=> 'ROLLOUT',
                        'sourceExperimentKey'=> null,
                        'sourceVariationKey'=> null
                    )
                )
            );

        $this->assertEquals(
            [
                'boolean_feature',
                'integer_single_variable_feature',
                'string_single_variable_feature',
                'mutex_group_feature'
            ],
            $optimizelyMock->getEnabledFeatures("user_id")
        );
    }

    public function testGetFeatureVariableValueForTypeCallsValidateInputs()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile))
            ->setMethods(array('validateInputs'))
            ->getMock();

        $featureKey = 'test_feature';
        $variableKey = 1.2;
        $userId = null;
        $inputArray = [
            'Feature Flag Key' => $featureKey,
            'Variable Key' => $variableKey,
            'User ID' => $userId
        ];

        // assert that validateInputs gets called with exactly same keys
        $optimizelyMock->expects($this->once())
            ->method('validateInputs')
            ->with($inputArray)
            ->willReturn(false);

        $this->assertNull($optimizelyMock->getFeatureVariableValueForType($featureKey, $variableKey, $userId));
    }

    public function testGetFeatureVariableValueForTypeWithInvalidUserID()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        $this->loggerMock->expects($this->exactly(6))
            ->method('log')
            ->with(Logger::ERROR, sprintf('Provided %s is in an invalid format.', Optimizely::USER_ID));

        $this->assertNull($this->optimizelyObject->getFeatureVariableValueForType("double_single_variable_feature", "double_variable", null, $userAttributes, "string"));
        $this->assertNull($this->optimizelyObject->getFeatureVariableValueForType("double_single_variable_feature", "double_variable", 5, $userAttributes, "string"));
        $this->assertNull($this->optimizelyObject->getFeatureVariableValueForType("double_single_variable_feature", "double_variable", 5.5, $userAttributes, "string"));
        $this->assertNull($this->optimizelyObject->getFeatureVariableValueForType("double_single_variable_feature", "double_variable", false, $userAttributes, "string"));
        $this->assertNull($this->optimizelyObject->getFeatureVariableValueForType("double_single_variable_feature", "double_variable", [], $userAttributes, "string"));
        $this->assertNull($this->optimizelyObject->getFeatureVariableValueForType("double_single_variable_feature", "double_variable", (object) array(), $userAttributes, "string"));
    }

    public function testGetFeatureVariableValueForTypeGivenFeatureFlagNotFound()
    {
        $feature_key = "abcd"; // Any string that is not a feature flag key in the data file

        //should return null and log a message when no feature flag found against a valid feature key
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::ERROR, "FeatureFlag Key \"{$feature_key}\" is not in datafile.");

        $this->assertNull($this->optimizelyObject->getFeatureVariableValueForType($feature_key, "double_variable", 'user_id'));
    }

    public function testGetFeatureVariableValueForTypeGivenFeatureVariableNotFound()
    {
        $feature_key = "boolean_feature"; // Any exisiting feature key in the data file
        $variable_key = "abcd"; // Any string that is not a variable key in the data file

        //should return null and log a message when no feature flag found against a valid feature key
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(
                Logger::ERROR,
                "No variable key \"{$variable_key}\" defined in datafile ".
                "for feature flag \"{$feature_key}\"."
            );

        $this->assertNull($this->optimizelyObject->getFeatureVariableValueForType($feature_key, $variable_key, 'user_id'));
    }

    public function testGetFeatureVariableValueForTypeGivenInvalidFeatureVariableType()
    {
        // should return null and log a message when a feature variable does exist but is
        // called for another type
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::ERROR, "Variable is of type 'double', but you requested it as type 'string'.");

        $this->assertNull($this->optimizelyObject->getFeatureVariableValueForType("double_single_variable_feature", "double_variable", "user_id", null, "string"));
    }

    public function testGetFeatureVariableValueForTypeGivenFeatureFlagIsNotEnabledForUser()
    {
        // should return default value
        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($this->optimizelyObject, $decisionServiceMock);

        // mock getVariationForFeature to return rolloutDecision
        $expectedDecision = new FeatureDecision(
            null,
            null,
            FeatureDecision::DECISION_SOURCE_ROLLOUT
        );
        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expectedDecision));

        $this->loggerMock->expects($this->exactly(1))
            ->method('log')
            ->with(
                Logger::INFO,
                "User 'user_id'is not in any variation, returning default value '14.99'."
            );

        $this->assertSame(
            $this->optimizelyObject->getFeatureVariableValueForType('double_single_variable_feature', 'double_variable', 'user_id', [], 'double'),
            '14.99'
        );
    }

    public function testGetFeatureVariableValueForTypeGivenFeatureFlagIsEnabledForUserAndVariableIsInVariation()
    {
        // should return specific value
        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($this->optimizelyObject, $decisionServiceMock);

        $experiment = $this->projectConfig->getExperimentFromKey('test_experiment_double_feature');
        $variation = $this->projectConfig->getVariationFromKey('test_experiment_double_feature', 'control');
        $expected_decision = new FeatureDecision(
            $experiment,
            $variation,
            FeatureDecision::DECISION_SOURCE_EXPERIMENT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expected_decision));

        $this->loggerMock->expects($this->exactly(1))
            ->method('log')
            ->with(
                Logger::INFO,
                "Returning variable value '42.42' for variation 'control' ".
                    "of feature flag 'double_single_variable_feature'"
            );

        $this->assertSame(
            $this->optimizelyObject->getFeatureVariableValueForType('double_single_variable_feature', 'double_variable', 'user_id', [], 'double'),
            '42.42'
        );
    }

    public function testGetFeatureVariableValueForTypeWithRolloutRule()
    {
        // should return specific value
        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($this->optimizelyObject, $decisionServiceMock);

        $featureFlag = $this->projectConfig->getFeatureFlagFromKey('boolean_single_variable_feature');
        $rolloutId = $featureFlag->getRolloutId();
        $rollout = $this->projectConfig->getRolloutFromId($rolloutId);
        $experiment = $rollout->getExperiments()[0];
        $expectedVariation = $experiment->getVariations()[0];
        $expectedDecision = new FeatureDecision(
            $experiment,
            $expectedVariation,
            FeatureDecision::DECISION_SOURCE_ROLLOUT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expectedDecision));

        $this->loggerMock->expects($this->exactly(1))
            ->method('log')
            ->with(
                Logger::INFO,
                "Returning variable value 'true' for variation '177771' ".
                "of feature flag 'boolean_single_variable_feature'"
            );

        $this->assertTrue($this->optimizelyObject->getFeatureVariableBoolean('boolean_single_variable_feature', 'boolean_variable', 'user_id', []));
    }

    public function testGetFeatureVariableValueForTypeGivenFeatureFlagIsEnabledForUserAndVariableNotInVariation()
    {
        // should return default value

        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($this->optimizelyObject, $decisionServiceMock);

        // Mock getVariationForFeature to return experiment/variation from a different feature
        $experiment = $this->projectConfig->getExperimentFromKey('test_experiment_integer_feature');
        $variation = $this->projectConfig->getVariationFromKey('test_experiment_integer_feature', 'control');
        $expected_decision = new FeatureDecision(
            $experiment,
            $variation,
            FeatureDecision::DECISION_SOURCE_EXPERIMENT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expected_decision));

        $this->loggerMock->expects($this->exactly(1))
            ->method('log')
            ->with(
                Logger::INFO,
                "Variable 'double_variable' is not used in variation 'control', returning default value '14.99'."
            );

        $this->assertSame(
            $this->optimizelyObject->getFeatureVariableValueForType(
                'double_single_variable_feature',
                'double_variable',
                'user_id',
                [],
                'double'
            ),
            '14.99'
        );
    }

    public function testGetFeatureVariableValueForTypeWithEmptyUserID()
    {
        // should return default value
        $decisionServiceMock = $this->getMockBuilder(DecisionService::class)
            ->setConstructorArgs(array($this->loggerMock, $this->projectConfig))
            ->setMethods(array('getVariationForFeature'))
            ->getMock();

        $decisionService = new \ReflectionProperty(Optimizely::class, '_decisionService');
        $decisionService->setAccessible(true);
        $decisionService->setValue($this->optimizelyObject, $decisionServiceMock);

        // mock getVariationForFeature to return rolloutDecision
        $expectedDecision = new FeatureDecision(
            null,
            null,
            FeatureDecision::DECISION_SOURCE_ROLLOUT
        );

        $decisionServiceMock->expects($this->exactly(1))
            ->method('getVariationForFeature')
            ->will($this->returnValue($expectedDecision));

        $this->loggerMock->expects($this->exactly(1))
            ->method('log')
            ->with(
                Logger::INFO,
                "User ''is not in any variation, returning default value '14.99'."
            );

        $this->assertSame(
            $this->optimizelyObject->getFeatureVariableValueForType('double_single_variable_feature', 'double_variable', '', [], 'double'),
            '14.99'
        );
    }

    public function testGetFeatureVariableBooleanCaseTrue()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('getFeatureVariableValueForType'))
            ->getMock();

        // assert that getFeatureVariableValueForType is called with expected arguments and mock to return 'true'
        $map = [['boolean_single_variable_feature', 'boolean_variable', 'user_id', [], 'boolean', 'true']];
        $optimizelyMock->expects($this->exactly(1))
            ->method('getFeatureVariableValueForType')
            ->with('boolean_single_variable_feature', 'boolean_variable', 'user_id', [], 'boolean')
            ->will($this->returnValueMap($map));

        $this->assertTrue($optimizelyMock->getFeatureVariableBoolean('boolean_single_variable_feature', 'boolean_variable', 'user_id', []));
    }

    public function testGetFeatureVariableBooleanCaseFalse()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('getFeatureVariableValueForType'))
            ->getMock();

        // assert that getFeatureVariableValueForType is called with expected arguments and mock to return any string but 'true'
        $map = [['boolean_single_variable_feature', 'boolean_variable', 'user_id', [], 'boolean', '14.33']];
        $optimizelyMock->expects($this->exactly(1))
            ->method('getFeatureVariableValueForType')
            ->with('boolean_single_variable_feature', 'boolean_variable', 'user_id', [], 'boolean')
            ->will($this->returnValueMap($map));

        $this->assertFalse($optimizelyMock->getFeatureVariableBoolean('boolean_single_variable_feature', 'boolean_variable', 'user_id', []));
    }

    public function testGetFeatureVariableIntegerWhenCasted()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('getFeatureVariableValueForType'))
            ->getMock();

        // assert that getFeatureVariableValueForType is called with expected arguments and mock to return a numeric string
        $map = [['integer_single_variable_feature', 'integer_variable', 'user_id', [], 'integer', '90']];
        $optimizelyMock->expects($this->exactly(1))
            ->method('getFeatureVariableValueForType')
            ->with('integer_single_variable_feature', 'integer_variable', 'user_id', [], 'integer')
            ->will($this->returnValueMap($map));

        $this->assertSame(
            $optimizelyMock->getFeatureVariableInteger('integer_single_variable_feature', 'integer_variable', 'user_id', []),
            90
        );
    }

    public function testGetFeatureVariableIntegerWhenNotCasted()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('getFeatureVariableValueForType'))
            ->getMock();

        // assert that getFeatureVariableValueForType is called with expected arguments and mock to return a non-numeric string
        $map = [['integer_single_variable_feature', 'integer_variable', 'user_id', [], 'integer', 'abc90']];
        $optimizelyMock->expects($this->exactly(1))
            ->method('getFeatureVariableValueForType')
            ->with('integer_single_variable_feature', 'integer_variable', 'user_id', [], 'integer')
            ->will($this->returnValueMap($map));

        $this->assertNull($optimizelyMock->getFeatureVariableInteger('integer_single_variable_feature', 'integer_variable', 'user_id', []));
    }

    public function testGetFeatureVariableDoubleWhenCasted()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('getFeatureVariableValueForType'))
            ->getMock();

        // assert that getFeatureVariableValueForType is called with expected arguments and mock to return a numeric string
        $map = [['double_single_variable_feature', 'double_variable', 'user_id', [], 'double', '5.789']];
        $optimizelyMock->expects($this->exactly(1))
            ->method('getFeatureVariableValueForType')
            ->with('double_single_variable_feature', 'double_variable', 'user_id', [], 'double')
            ->will($this->returnValueMap($map));

        $this->assertSame(
            $optimizelyMock->getFeatureVariableDouble('double_single_variable_feature', 'double_variable', 'user_id', []),
            5.789
        );
    }

    public function testGetFeatureVariableDoubleWhenNotCasted()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('getFeatureVariableValueForType'))
            ->getMock();

        // assert that getFeatureVariableValueForType is called with expected arguments and mock to return a non-numeric string
        $map = [['double_single_variable_feature', 'double_variable', 'user_id', [], 'double', 'abc5.789']];
        $optimizelyMock->expects($this->exactly(1))
            ->method('getFeatureVariableValueForType')
            ->with('double_single_variable_feature', 'double_variable', 'user_id', [], 'double')
            ->will($this->returnValueMap($map));

        $this->assertNull($optimizelyMock->getFeatureVariableDouble('double_single_variable_feature', 'double_variable', 'user_id', []));
    }

    public function testGetFeatureVariableString()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('getFeatureVariableValueForType'))
            ->getMock();

        $map = [['string_single_variable_feature', 'string_variable', 'user_id', [], 'string', '59abc0p']];
        $optimizelyMock->expects($this->exactly(1))
            ->method('getFeatureVariableValueForType')
            ->with('string_single_variable_feature', 'string_variable', 'user_id', [], 'string')
            ->will($this->returnValueMap($map));

        $this->assertSame(
            $optimizelyMock->getFeatureVariableString('string_single_variable_feature', 'string_variable', 'user_id', []),
            '59abc0p'
        );
    }

    public function testGetFeatureVariableMethodsReturnNullWhenGetVariableValueForTypeReturnsNull()
    {
        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('getFeatureVariableValueForType'))
            ->getMock();
        $optimizelyMock->expects($this->exactly(4))
            ->method('getFeatureVariableValueForType')
            ->willReturn(null);

        $this->assertNull(
            $optimizelyMock->getFeatureVariableBoolean(
                'boolean_single_variable_feature',
                'boolean_variable',
                'user_id',
                []
            )
        );
        $this->assertNull(
            $optimizelyMock->getFeatureVariableString(
                'string_single_variable_feature',
                'string_variable',
                'user_id',
                []
            )
        );
        $this->assertNull(
            $optimizelyMock->getFeatureVariableDouble(
                'double_single_variable_feature',
                'double_variable',
                'user_id',
                []
            )
        );
        $this->assertNull(
            $optimizelyMock->getFeatureVariableInteger(
                'integer_single_variable_feature',
                'integer_variable',
                'user_id',
                []
            )
        );
    }

    public function testGetFeatureVariableReturnsVariableValueForTypedAudienceMatch()
    {
        $userAttributes = [
            'lasers' => 71
        ];

        // Should be included in the feature test via greater-than match audience with id '3468206647'
        $this->assertEquals('xyz', $this->optimizelyTypedAudienceObject->getFeatureVariableString('feat_with_var', 'x', 'user1', $userAttributes));

        $userAttributes = [
            'should_do_it' => true
        ];

        // Should be included in the feature test via exact match boolean audience with id '3468206643'
        $this->assertEquals('xyz', $this->optimizelyTypedAudienceObject->getFeatureVariableString('feat_with_var', 'x', 'user1', $userAttributes));
    }

    public function testGetFeatureVariableReturnsDefaultValueForTypedAudienceMismatch()
    {
        $userAttributes = [
            'lasers' => 50
        ];

        // Should be included in the feature test via greater-than match audience with id '3468206647'
        $this->assertEquals('x', $this->optimizelyTypedAudienceObject->getFeatureVariableString('feat_with_var', 'x', 'user1', $userAttributes));
    }

    public function testGetFeatureVariableReturnsVariableValueForComplexAudienceMatch()
    {
        $userAttributes = [
            'house' => 'Gryffindor',
            'lasers' => 700
        ];

        // Should be included via exact match string audience with id '3468206642', and
        // greater than audience with id '3468206647'
        $this->assertSame(150, $this->optimizelyTypedAudienceObject->getFeatureVariableInteger('feat2_with_var', 'z', 'user1', $userAttributes));
    }

    public function testGetFeatureVariableReturnsDefaultValueForComplexAudienceMismatch()
    {
        $userAttributes = [];

        // Should be excluded - no audiences match with no attributes
        $this->assertSame(10, $this->optimizelyTypedAudienceObject->getFeatureVariableInteger('feat2_with_var', 'z', 'user1', $userAttributes));
    }

    public function testSendImpressionEventWithNoAttributes()
    {
        $optlyObject = new OptimizelyTester($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        // verify that createImpressionEvent is called
        $this->eventBuilderMock->expects($this->once())
            ->method('createImpressionEvent')
            ->with(
                $this->projectConfig,
                'group_experiment_1',
                'group_exp_1_var_2',
                'user_1',
                null
            )
            ->willReturn(
                new LogEvent(
                    'logx.optimizely.com/decision',
                    ['param1' => 'val1', 'param2' => 'val2'],
                    'POST',
                    []
                )
            );

        // verify that sendNotifications is called with expected params
        $arrayParam = array(
            $this->projectConfig->getExperimentFromKey('group_experiment_1'),
            'user_1',
            null,
            $this->projectConfig->getVariationFromKey('group_experiment_1', 'group_exp_1_var_2'),
            new LogEvent(
                'logx.optimizely.com/decision',
                ['param1' => 'val1', 'param2' => 'val2'],
                'POST',
                []
            )
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::ACTIVATE,
                $arrayParam
            );

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        $optlyObject->notificationCenter = $this->notificationCenterMock;

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(
                Logger::INFO,
                'Activating user "user_1" in experiment "group_experiment_1".'
            );
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(
                Logger::DEBUG,
                'Dispatching impression event to URL logx.optimizely.com/decision with params {"param1":"val1","param2":"val2"}.'
            );

        $optlyObject->sendImpressionEvent('group_experiment_1', 'group_exp_1_var_2', 'user_1', null);
    }

    public function testSendImpressionEventDispatchFailure()
    {
        $optlyObject = new OptimizelyTester($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $eventDispatcherMock = $this->getMockBuilder(DefaultEventDispatcher::class)
            ->setMethods(array('dispatchEvent'))
            ->getMock();

        $eventDispatcher = new \ReflectionProperty(Optimizely::class, '_eventDispatcher');
        $eventDispatcher->setAccessible(true);
        $eventDispatcher->setValue($optlyObject, $eventDispatcherMock);

        $eventDispatcherMock->expects($this->once())
            ->method('dispatchEvent')
            ->will($this->throwException(new Exception));

        $this->loggerMock->expects($this->at(2))
            ->method('log')
            ->with(Logger::ERROR, 'Unable to dispatch impression event. Error ');

        $optlyObject->sendImpressionEvent('test_experiment', 'control', 'test_user', []);
    }

    public function testSendImpressionEventWithAttributes()
    {
        $optlyObject = new OptimizelyTester($this->datafile, new ValidEventDispatcher(), $this->loggerMock);

        $userAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        // verify that createImpressionEvent is called
        $this->eventBuilderMock->expects($this->once())
            ->method('createImpressionEvent')
            ->with(
                $this->projectConfig,
                'test_experiment',
                'control',
                'test_user',
                $userAttributes
            )
            ->willReturn(new LogEvent('logx.optimizely.com/decision', ['param1' => 'val1'], 'POST', []));

        // verify that sendNotifications is called with expected params
        $arrayParam = array(
            $this->projectConfig->getExperimentFromKey('test_experiment'),
            'test_user',
            $userAttributes,
            $this->projectConfig->getVariationFromKey('test_experiment', 'control'),
            new LogEvent(
                'logx.optimizely.com/decision',
                ['param1' => 'val1'],
                'POST',
                []
            )
        );

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(
                NotificationType::ACTIVATE,
                $arrayParam
            );

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, 'Activating user "test_user" in experiment "test_experiment".');
        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(
                Logger::DEBUG,
                'Dispatching impression event to URL logx.optimizely.com/decision with params {"param1":"val1"}.'
            );

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        $optlyObject->notificationCenter = $this->notificationCenterMock;

        $optlyObject->sendImpressionEvent('test_experiment', 'control', 'test_user', $userAttributes);
    }

    /*
    * test ValidateInputs method validates and logs for different and multiple keys
    */
    public function testValidateInputs()
    {
        $optlyObject = new OptimizelyTester($this->datafile, null, $this->loggerMock);

        $INVALID_USER_ID_LOG = 'Provided User ID is in an invalid format.';
        $INVALID_EVENT_KEY_LOG = 'Provided Event Key is in an invalid format.';
        $INVALID_RANDOM_KEY_LOG = 'Provided ABCD is in an invalid format.';

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::ERROR, $INVALID_USER_ID_LOG);
        $this->assertFalse($optlyObject->validateInputs([Optimizely::USER_ID => null]));

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::ERROR, $INVALID_RANDOM_KEY_LOG);
        $this->assertFalse($optlyObject->validateInputs(['ABCD' => null]));

        // Verify that multiple messages are logged.
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::ERROR, $INVALID_USER_ID_LOG);

        $this->loggerMock->expects($this->at(1))
            ->method('log')
            ->with(Logger::ERROR, $INVALID_EVENT_KEY_LOG);

        $this->assertFalse($optlyObject->validateInputs([Optimizely::EVENT_KEY => null, Optimizely::USER_ID => null]));

        // Verify that logger level is taken into account
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, $INVALID_RANDOM_KEY_LOG);
        $this->assertFalse($optlyObject->validateInputs(['ABCD' => null], Logger::INFO));

        // Verify that it returns true and nothing is logged if valid values given
        $this->loggerMock->expects($this->never())
            ->method('log');
        $this->assertTrue($optlyObject->validateInputs([Optimizely::EVENT_KEY => 'test_event', Optimizely::USER_ID => 'test_user']));
    }

    /*
    * test ValidateInputs method only returns true for non-empty string
    */
    public function testValidateInputsWithDifferentValues()
    {
        $optlyObject = new OptimizelyTester($this->datafile);

        $this->assertTrue($optlyObject->validateInputs(['key' => '0']));
        $this->assertTrue($optlyObject->validateInputs(['key' => 'test_user']));

        $this->assertFalse($optlyObject->validateInputs(['key' => '']));
        $this->assertFalse($optlyObject->validateInputs(['key' => null]));
        $this->assertFalse($optlyObject->validateInputs(['key' => false]));
        $this->assertFalse($optlyObject->validateInputs(['key' => true]));
        $this->assertFalse($optlyObject->validateInputs(['key' => 2]));
        $this->assertFalse($optlyObject->validateInputs(['key' => 2.0]));
        $this->assertFalse($optlyObject->validateInputs(['key' => array()]));
    }
}
