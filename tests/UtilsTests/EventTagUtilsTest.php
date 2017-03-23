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
    public function testGetRevenueValueInvalidArgs() {
        $this->assertNull(EventTagUtils::getRevenueValue(null));
        $this->assertNull(EventTagUtils::getRevenueValue(0.5));
        $this->assertNull(EventTagUtils::getRevenueValue(65536));
        $this->assertNull(EventTagUtils::getRevenueValue(9223372036854775807));
        $this->assertNull(EventTagUtils::getRevenueValue('65536'));
        $this->assertNull(EventTagUtils::getRevenueValue(true));
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
        $this->assertNull(EventTagUtils::getRevenueValue(array('revenue' => array(1, 2, 3))));
    }

    public function testGetRevenueValueWithRevenueTag() {
        $this->assertEquals(65536, EventTagUtils::getRevenueValue(array('revenue' => 65536)));
        $this->assertEquals(9223372036854775807, EventTagUtils::getRevenueValue(array('revenue' => 9223372036854775807)));
        $this->assertEquals(0, EventTagUtils::getRevenueValue(array('revenue' => 0)));
    }
}
