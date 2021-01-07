<?php
/**
 * Copyright 2021, Optimizely
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

require(dirname(__FILE__).'/TestData.php');

use Exception;
use TypeError;

use Optimizely\Logger\NoOpLogger;
use Optimizely\Optimizely;
use Optimizely\OptimizelyUserContext;

class OptimizelyUserContextTest extends \PHPUnit_Framework_TestCase
{
    private $datafile;
    private $loggerMock;
    private $optimizelyObject;

    public function setUp()
    {
        $this->datafile = DATAFILE;

        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();


        $this->optimizelyObject = new Optimizely($this->datafile, null, $this->loggerMock);
    }
    public function testOptimizelyUserContextIsCreatedWithExpectedValues()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];
        $optUserContext = new OptimizelyUserContext($this->optimizelyObject, $userId, $attributes);

        $this->assertEquals($userId, $optUserContext->getUserId());
        $this->assertEquals($attributes, $optUserContext->getAttributes());
        $this->assertSame($this->optimizelyObject, $optUserContext->getOptimizely());
    }

    public function testOptimizelyUserContextThrowsErrorWhenNonArrayPassedAsAttributes()
    {
        $userId = 'test_user';

        try {
            $optUserContext = new OptimizelyUserContext($this->optimizelyObject, $userId, 'HelloWorld');
        } catch (Exception $exception) {
            return;
        } catch (TypeError $exception) {
            return;
        }

        $this->fail('Unexpected behavior. UserContext should have thrown an error.');
    }

    public function testSetAttribute()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];
        $optUserContext = new OptimizelyUserContext($this->optimizelyObject, $userId, $attributes);

        $this->assertEquals($attributes, $optUserContext->getAttributes());

        $optUserContext->setAttribute('color', 'red');
        $this->assertEquals([
            "browser" => "chrome",
            "color" => "red"
        ], $optUserContext->getAttributes());
    }

    public function testSetAttributeOverridesValueOfExistingKey()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];
        $optUserContext = new OptimizelyUserContext($this->optimizelyObject, $userId, $attributes);

        $this->assertEquals($attributes, $optUserContext->getAttributes());

        $optUserContext->setAttribute('browser', 'firefox');
        $this->assertEquals(["browser" => "firefox"], $optUserContext->getAttributes());
    }

    public function testSetAttributeWhenNoAttributesProvidedInConstructor()
    {
        $userId = 'test_user';
        $optUserContext = new OptimizelyUserContext($this->optimizelyObject, $userId);

        $this->assertEquals([], $optUserContext->getAttributes());

        $optUserContext->setAttribute('browser', 'firefox');
        $this->assertEquals(["browser" => "firefox"], $optUserContext->getAttributes());
    }

    public function testDecideCallsAndReturnsOptimizelyDecideAPI()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];


        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('decide'))
            ->getMock();


        $optUserContext = new OptimizelyUserContext($optimizelyMock, $userId, $attributes);

        //assert that decide is called with expected params
        $optimizelyMock->expects($this->exactly(1))
        ->method('decide')
        ->with(
            $optUserContext,
            'test_feature',
            ['DISABLE_DECISION_EVENT', 'ENABLED_FLAGS_ONLY']
        )
        ->will($this->returnValue('Mocked return value'));

        $this->assertEquals(
            'Mocked return value',
            $optUserContext->decide('test_feature', ['DISABLE_DECISION_EVENT', 'ENABLED_FLAGS_ONLY'])
        );
    }

    public function testDecideAllCallsAndReturnsOptimizelyDecideAllAPI()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('decideAll'))
            ->getMock();


        $optUserContext = new OptimizelyUserContext($optimizelyMock, $userId, $attributes);

        //assert that decideAll is called with expected params
        $optimizelyMock->expects($this->exactly(1))
        ->method('decideAll')
        ->with(
            $optUserContext,
            ['ENABLED_FLAGS_ONLY']
        )
        ->will($this->returnValue('Mocked return value'));

        $this->assertEquals(
            'Mocked return value',
            $optUserContext->decideAll(['ENABLED_FLAGS_ONLY'])
        );
    }

    public function testDecideForKeysCallsAndReturnsOptimizelyDecideForKeysAPI()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('decideForKeys'))
            ->getMock();


        $optUserContext = new OptimizelyUserContext($optimizelyMock, $userId, $attributes);

        //assert that decideForKeys is called with expected params
        $optimizelyMock->expects($this->exactly(1))
        ->method('decideForKeys')
        ->with(
            $optUserContext,
            ['test_feature', 'test_experiment'],
            ['DISABLE_DECISION_EVENT']
        )
        ->will($this->returnValue('Mocked return value'));

        $this->assertEquals(
            'Mocked return value',
            $optUserContext->decideForKeys(['test_feature', 'test_experiment'], ['DISABLE_DECISION_EVENT'])
        );
    }

    public function testTrackEventCallsAndReturnsOptimizelyTrackAPI()
    {
        $userId = 'test_user';
        $attributes = [];
        $eventKey = "test_event";
        $eventTags = [ "revenue" => 50];

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('track'))
            ->getMock();


        $optUserContext = new OptimizelyUserContext($optimizelyMock, $userId, $attributes);

        //assert that track is called with expected params
        $optimizelyMock->expects($this->exactly(1))
        ->method('track')
        ->with(
            $eventKey,
            $userId,
            $attributes,
            $eventTags
        )
        ->will($this->returnValue('Mocked return value'));

        $this->assertEquals(
            'Mocked return value',
            $optUserContext->trackEvent($eventKey, $eventTags)
        );
    }

    public function testJsonSerialize()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];
        $optUserContext = new OptimizelyUserContext($this->optimizelyObject, $userId, $attributes);

        $this->assertEquals([
            'userId' => $userId,
            'attributes' => $attributes
        ], json_decode(json_encode($optUserContext), true));
    }
}
