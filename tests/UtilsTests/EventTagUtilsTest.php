<?php
/**
 * Copyright 2017-2018, Optimizely
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

use Optimizely\Utils\EventTagUtils;
use Optimizely\Logger\NoOpLogger;
use Monolog\Logger;

class EventTagUtilsTest extends \PHPUnit_Framework_TestCase
{
    // The size of a float is platform-dependent, although a maximum of ~1.8e308 with a precision of roughly 14 decimal digits is a common value (the 64 bit IEEE format). http://php.net/manual/en/language.types.float.php
    // PHP_FLOAT_MAX - Available as of PHP7.2.0 http://php.net/manual/en/reserved.constants.php
    const max_float = 1.8e307;
    const min_float = -1.8e307;
    protected $loggerMock;

    protected function setUp()
    {
        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();
    }

    public function testGetRevenueValueWithUndefinedTags()
    {
        $this->loggerMock->expects($this->exactly(3))
            ->method('log')
            ->with(Logger::DEBUG, 'Event tags is undefined.');

        $this->assertNull(EventTagUtils::getRevenueValue(null, $this->loggerMock));
        $this->assertNull(EventTagUtils::getRevenueValue(array(), $this->loggerMock));
        $this->assertNull(EventTagUtils::getRevenueValue(false, $this->loggerMock));
    }

    public function testGetRevenueValueWithNonDictionaryTags()
    {
        $this->loggerMock->expects($this->exactly(5))
            ->method('log')
            ->with(Logger::DEBUG, 'Event tags is not a dictionary.');

        $this->assertNull(EventTagUtils::getRevenueValue(0.5, $this->loggerMock));
        $this->assertNull(EventTagUtils::getRevenueValue(65536, $this->loggerMock));
        $this->assertNull(EventTagUtils::getRevenueValue(9223372036854775807, $this->loggerMock));
        $this->assertNull(EventTagUtils::getRevenueValue('65536', $this->loggerMock));
        $this->assertNull(EventTagUtils::getRevenueValue(true, $this->loggerMock));
    }


    public function testGetRevenueValueNoRevenueTag()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('log')
            ->with(Logger::DEBUG, "The revenue key is not defined in the event tags or is null.");

        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => null), $this->loggerMock));
        $this->assertNull(EventTagUtils::getRevenueValue(array('non-revenue' => 42), $this->loggerMock));
    }

    public function testGetRevenueValueWithNonNumericRevenueTag()
    {
        $this->loggerMock->expects($this->exactly(4))
            ->method('log')
            ->with(Logger::DEBUG, "Revenue value is not an integer or float, or is not a numeric string.");

        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => 'optimizely'), $this->loggerMock));
        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => true), $this->loggerMock));
        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => false), $this->loggerMock));
        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => array(1, 2, 3)), $this->loggerMock));
    }

    public function testGetRevenueValueWithNonParsableRevenueTag()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('log')
            ->with(Logger::DEBUG, "Revenue value couldn't be parsed as an integer.");

        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => 0.5), $this->loggerMock));
        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => "42.5"), $this->loggerMock));
    }

    public function testGetRevenueValueWithValidRevenueTag()
    {
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The revenue value 65536 will be sent to results.");
        $this->assertSame(65536, EventTagUtils::getRevenueValue(array('revenue' => 65536), $this->loggerMock));

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The revenue value 65536 will be sent to results.");
        $this->assertSame(65536, EventTagUtils::getRevenueValue(array('revenue' => "65536"), $this->loggerMock));

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The revenue value 65536 will be sent to results.");
        $this->assertSame(65536, EventTagUtils::getRevenueValue(array('revenue' => 65536.0), $this->loggerMock));

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The revenue value 65536 will be sent to results.");
        $this->assertSame(65536, EventTagUtils::getRevenueValue(array('revenue' => "65536.0"), $this->loggerMock));

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The revenue value 9223372036854775807 will be sent to results.");
        $this->assertSame(9223372036854775807, EventTagUtils::getRevenueValue(array('revenue' => 9223372036854775807), $this->loggerMock));

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The revenue value 0 will be sent to results.");
        $this->assertSame(0, EventTagUtils::getRevenueValue(array('revenue' => 0), $this->loggerMock));

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The revenue value 0 will be sent to results.");
        $this->assertSame(0, EventTagUtils::getRevenueValue(array('revenue' => 0.0), $this->loggerMock));
    }

    public function testGetNumericValueWithUndefinedTags()
    {
        $this->loggerMock->expects($this->any())
            ->method('log')
            ->with(Logger::DEBUG, 'Event tags is undefined.');

        $this->assertNull(EventTagUtils::getNumericValue(null, $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(false, $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(array(), $this->loggerMock));
    }

    public function testGetNumericValueWithNonDictionaryTags()
    {
        $this->loggerMock->expects($this->any())
            ->method('log')
            ->with(Logger::DEBUG, 'Event tags is not a dictionary.');

        $this->assertNull(EventTagUtils::getNumericValue(0.5, $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(65536, $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(9223372036854775807, $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue('65536', $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(true, $this->loggerMock));
    }

    public function testGetNumericValueWithUndefinedValueTag()
    {
        $this->loggerMock->expects($this->any())
            ->method('log')
            ->with(Logger::DEBUG, 'The numeric metric key is not defined in the event tags or is null.');

        $this->assertNull(EventTagUtils::getNumericValue(array('non-value' => 42), $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(array('value' => null), $this->loggerMock));
    }

    public function testGetNumericValueWithNonNumericValueTag()
    {
        $this->loggerMock->expects($this->any())
            ->method('log')
            ->with(Logger::DEBUG, 'Numeric metric value is not an integer or float, or is not a numeric string.');

        $this->assertNull(EventTagUtils::getNumericValue(array('value' => 'abcd'), $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(array('value' => true), $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(array('value' => array(1, 2, 3)),$this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(array('value' => false), $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(array('value' => array()), $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(array('value' => '1,234'), $this->loggerMock));
    }

    public function testGetNumericValueWithINForNANValueTag()
    {
        $this->loggerMock->expects($this->any())
            ->method('log')
            ->with(Logger::DEBUG, 'Provided numeric value is in an invalid format.');

        $this->assertNull(EventTagUtils::getNumericValue(array('value' => floatval(NAN)), $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(array('value' => floatval(INF)), $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(array('value' => floatval(-INF)), $this->loggerMock));
        $this->assertNull(EventTagUtils::getNumericValue(array('value' => (self::max_float*10)), $this->loggerMock));
    }

    // Test that the correct numeric value is returned
    public function testGetNumericValueWithValueTag()
    {
        // An integer should be cast to a float
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The numeric metric value 12345 will be sent to results.");
        $this->assertSame(12345.0, EventTagUtils::getNumericValue(array('value' => 12345), $this->loggerMock));

        // A string should be cast to a float
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The numeric metric value 12345 will be sent to results.");
        $this->assertSame(12345.0, EventTagUtils::getNumericValue(array('value' => '12345'), $this->loggerMock));

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The numeric metric value 1.2345 will be sent to results.");
        $this->assertSame(1.2345, EventTagUtils::getNumericValue(array('value' => 1.2345), $this->loggerMock));

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The numeric metric value ".self::max_float." will be sent to results.");
        $this->assertSame(self::max_float, EventTagUtils::getNumericValue(array('value' => self::max_float), $this->loggerMock));

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The numeric metric value ".self::min_float." will be sent to results.");
        $this->assertSame(self::min_float, EventTagUtils::getNumericValue(array('value' => self::min_float), $this->loggerMock));

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "The numeric metric value 0 will be sent to results.");
        $this->assertSame(0.0, EventTagUtils::getNumericValue(array('value' => 0.0), $this->loggerMock));
    }
}
