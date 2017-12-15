<?php
/**
 * Copyright 2017, Optimizely
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

use Monolog\Logger;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Utils\VariableTypeUtils;

class VariableTypeUtilsTest extends \PHPUnit_Framework_TestCase
{
    protected $loggerMock;
    protected $variableUtilObj;

    protected function setUp()
    {
        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();

        $this->variableUtilObj = new VariableTypeUtils();
    }

    public function testValueCastingToBoolean()
    {
        $this->assertTrue($this->variableUtilObj->castStringToType('true', 'boolean'));
        $this->assertTrue($this->variableUtilObj->castStringToType('True', 'boolean'));
        $this->assertFalse($this->variableUtilObj->castStringToType('false', 'boolean'));
        $this->assertFalse($this->variableUtilObj->castStringToType('somestring', 'boolean'));
    }

    public function testValueCastingToInteger()
    {
        $this->assertSame(1000, $this->variableUtilObj->castStringToType('1000', 'integer'));
        $this->assertSame(123, $this->variableUtilObj->castStringToType('123', 'integer'));

        // should return nulll and log a message if value can not be casted to an integer
        $value = '123.5'; // any string with non-decimal digits
        $type = 'integer';
        $this->loggerMock->expects($this->exactly(1))
            ->method('log')
            ->with(
                Logger::ERROR,
                "Unable to cast variable value '{$value}' to type '{$type}'."
            );

        $this->assertNull($this->variableUtilObj->castStringToType($value, $type, $this->loggerMock));
    }

    public function testValueCastingToDouble()
    {
        $this->assertSame(1000.0, $this->variableUtilObj->castStringToType('1000', 'double'));
        $this->assertSame(3.0, $this->variableUtilObj->castStringToType('3.0', 'double'));
        $this->assertSame(13.37, $this->variableUtilObj->castStringToType('13.37', 'double'));

        // should return nil and log a message if value can not be casted to a double
        $value = 'any-non-numeric-string';
        $type = 'double';
        $this->loggerMock->expects($this->exactly(1))
            ->method('log')
            ->with(
                Logger::ERROR,
                "Unable to cast variable value '{$value}' to type '{$type}'."
            );

        $this->assertNull($this->variableUtilObj->castStringToType($value, $type, $this->loggerMock));
    }

    public function testValueCastingToString()
    {
        $this->assertSame('13.37', $this->variableUtilObj->castStringToType('13.37', 'string'));
        $this->assertSame('a string', $this->variableUtilObj->castStringToType('a string', 'string'));
        $this->assertSame('3', $this->variableUtilObj->castStringToType('3', 'string'));
        $this->assertSame('false', $this->variableUtilObj->castStringToType('false', 'string'));
    }
}
