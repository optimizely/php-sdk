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
use Optimizely\Bucketer;
use Optimizely\Event\LogEvent;
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
    private $optimizelyObject;
    private $projectConfig;

    public function setUp()
    {
        $this->datafile = DATAFILE;
        $this->projectConfig = new ProjectConfig($this->datafile);
        $this->optimizelyObject = new Optimizely($this->datafile);

        // Mock EventBuilder
        $this->eventBuilderMock = $this->getMockBuilder(EventBuilder::class)
            ->setMethods(array('createImpressionEvent', 'createConversionEvent'))
            ->setConstructorArgs(array(new Bucketer()))
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
    }

    public function testValidateInputsInvalidFileJsonValidationSkipped()
    {
        $validateInputsMethod = new \ReflectionMethod('Optimizely\Optimizely', 'validateInputs');
        $validateInputsMethod->setAccessible(true);

        $this->assertTrue(
            $validateInputsMethod->invoke(new Optimizely('Random datafile', null, null, null, true),
            'Random datafile', true)
        );
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

    public function testActivateNoAudienceNoAttributes()
    {
        $this->eventBuilderMock->expects($this->once())
            ->method('createImpressionEvent')
            ->with(
                $this->projectConfig,
                $this->projectConfig->getExperimentFromKey('group_experiment_1'),
                '7722360022', 'user_1', null
            )
            ->willReturn(new LogEvent('logx.optimizely.com', [], 'POST', []));

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher());

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

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher());

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
            ->willReturn(new LogEvent('logx.optimizely.com', [], 'POST', []));

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher());

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

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher());

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call activate
        $this->assertNull($optlyObject->activate('paused_experiment', 'test_user', null));
    }

    public function testGetVariationAudienceMatch()
    {
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
        $this->assertNull($this->optimizelyObject->getVariation('test_experiment', 'test_user'));
    }

    public function testGetVariationExperimentNotRunning()
    {
        $this->assertNull($this->optimizelyObject->getVariation('paused_experiment', 'test_user'));
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
            ->willReturn(new LogEvent('logx.optimizely.com', [], 'POST', []));

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher());

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
            ->willReturn(new LogEvent('logx.optimizely.com', [], 'POST', []));

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher());

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
            ->willReturn(new LogEvent('logx.optimizely.com', [], 'POST', []));

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher());

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
            ->willReturn(new LogEvent('logx.optimizely.com', [], 'POST', []));

        $optlyObject = new Optimizely($this->datafile, new ValidEventDispatcher());

        $eventBuilder = new \ReflectionProperty(Optimizely::class, '_eventBuilder');
        $eventBuilder->setAccessible(true);
        $eventBuilder->setValue($optlyObject, $this->eventBuilderMock);

        // Call track
        $optlyObject->track('purchase', 'test_user', $userAttributes, 42);
    }
}
