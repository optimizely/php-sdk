<?php
/**
 * Copyright 2020, Optimizely
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

use Optimizely\ErrorHandler\NoOpErrorHandler;
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
}
