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

use Optimizely\Utils\EventTagUtils;

class EventTagUtilsTest extends \PHPUnit_Framework_TestCase
{
    // The size of a float is platform-dependent, although a maximum of ~1.8e308 with a precision of roughly 14 decimal digits is a common value (the 64 bit IEEE format). http://php.net/manual/en/language.types.float.php
    // PHP_FLOAT_MAX - Available as of PHP7.2.0 http://php.net/manual/en/reserved.constants.php
    const max_float = 1.8e307;
    const min_float = -1.8e307;


    public function testGetRevenueValueInvalidArgs() {
        $this->assertNull(EventTagUtils::getRevenueValue(null));
        $this->assertNull(EventTagUtils::getRevenueValue(0.5));
        $this->assertNull(EventTagUtils::getRevenueValue(65536));
        $this->assertNull(EventTagUtils::getRevenueValue(9223372036854775807));
        $this->assertNull(EventTagUtils::getRevenueValue('65536'));
        $this->assertNull(EventTagUtils::getRevenueValue(true));
        $this->assertNull(EventTagUtils::getRevenueValue(false));
    }
    public function testGetRevenueValueNoRevenueTag() {
        $this->assertNull(EventTagUtils::getRevenueValue(array()));
        $this->assertNull(EventTagUtils::getRevenueValue(array('non-revenue' => 42)));
    }

    public function testGetRevenueValueWithInvalidRevenueTag() {
        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => null)));
        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => 0.5)));
        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => '65536')));
        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => true)));
        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => false)));
        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => array(1, 2, 3))));
    }

    public function testGetRevenueValueWithRevenueTag() {
        $this->assertSame(65536, EventTagUtils::getRevenueValue(array('revenue' => 65536)));
        $this->assertSame(9223372036854775807, EventTagUtils::getRevenueValue(array('revenue' => 9223372036854775807)));
        $this->assertSame(0, EventTagUtils::getRevenueValue(array('revenue' => 0)));
    }

    public function testGetNumericValueInvalidArgs() {
       $this->assertNull(EventTagUtils::getNumericValue(null));
       $this->assertNull(EventTagUtils::getNumericValue(0.5));
       $this->assertNull(EventTagUtils::getNumericValue(65536));
       $this->assertNull(EventTagUtils::getNumericValue(9223372036854775807));
       $this->assertNull(EventTagUtils::getNumericValue('65536'));
       $this->assertNull(EventTagUtils::getNumericValue(true));
       $this->assertNull(EventTagUtils::getNumericValue(false));
   }

   public function testGetNumericValueNoValueTag() {
        $this->assertNull(EventTagUtils::getNumericValue(array()));
        $this->assertNull(EventTagUtils::getNumericValue(array('non-value' => 42)));
    }

    public function testGetNumericValueWithInvalidValueTag() {
        $this->assertNull(EventTagUtils::getRevenueValue(array('value' => null)));
        $this->assertNull(EventTagUtils::getRevenueValue(array('value' => 'abcd')));
        $this->assertNull(EventTagUtils::getRevenueValue(array('value' => true)));
        $this->assertNull(EventTagUtils::getRevenueValue(array('value' => false)));
        $this->assertNull(EventTagUtils::getRevenueValue(array('value' => array(1, 2, 3))));
        $this->assertNull(EventTagUtils::getRevenueValue(array('value' => array())));
        $this->assertNull(EventTagUtils::getRevenueValue(array('value' => '1,234')));
        $this->assertNull(EventTagUtils::getRevenueValue(array('value' => floatval(NAN))));
        $this->assertNull(EventTagUtils::getRevenueValue(array('value' => (self::max_float*10))));
        $this->assertNull(EventTagUtils::getRevenueValue(array('value' => floatval(INF))));
        $this->assertNull(EventTagUtils::getRevenueValue(array('value' => floatval(-INF))));
    }

    // Test that the correct numeric value is returned
    public function testGetNumericValueWithValueTag() {
        # An integer should be cast to a float
        $this->assertSame(12345.0, EventTagUtils::getNumericValue(array('value' => 12345)));

        # A string should be cast to a float
        $this->assertSame(12345.0, EventTagUtils::getNumericValue(array('value' => '12345'))); 

        $this->assertSame(0.0, EventTagUtils::getNumericValue(array('value' => 0.0)));

        $this->assertSame(1.2345, EventTagUtils::getNumericValue(array('value' => 1.2345))); 

        $this->assertSame(self::max_float, EventTagUtils::getNumericValue(array('value' => self::max_float)));

        $this->assertSame(self::min_float, EventTagUtils::getNumericValue(array('value' => self::min_float)));
    }

}
