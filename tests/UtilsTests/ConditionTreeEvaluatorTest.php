<?php
/**
 * Copyright 2018, Optimizely
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

use Optimizely\Utils\ConditionTreeEvaluator;

class ConditionTreeEvaluatorTest extends \PHPUnit_Framework_TestCase
{   
    public function setUp()
    {
        $this->conditionA = (object)[
            'name' => 'browser_type',
            'value' => 'safari',
            'type' => 'custom_attribute'
        ];

        $this->conditionB = (object)[
            'name' => 'device_model',
            'value' => 'iphone6',
            'type' => 'custom_attribute'
        ];

        $this->conditionC = (object)[
            'name' => 'location',
            'match' => 'exact',
            'type' => 'custom_attribute',
            'value' => 'CA'
        ];

        $this->conditionTreeEvaluator = new ConditionTreeEvaluator();
    }

    protected function getLeafEvaluator($a, $b = null, $c = null) {
        $numOfCalls = 0;

        $leafEvaluator = function ($some_arg) use (&$numOfCalls, $a, $b, $c) {
            $numOfCalls++;
            if($numOfCalls == 1)
                return $a;
            if($numOfCalls == 2)
                return $b;
            if($numOfCalls == 3)
                return $c;

            return null;
        };

        return $leafEvaluator;
    }

    public function testEvaluateReturnsTrueWhenLeafConditionReturnsTrue()
    {   
        $leafEvaluator = function() {
            return True;
        };

        $this->assertTrue(
            $this->conditionTreeEvaluator->evaluate($this->conditionA, $this->getLeafEvaluator(True))
        );
    }

    // Test that andEvaluator returns false when any one condition evaluates to false. 
    public function testAndEvaluatorReturnsFalseWhenAnyOneConditionEvaluatesFalse()
    {   
        $this->assertFalse(
            $this->conditionTreeEvaluator->evaluate(
                ['and', $this->conditionA, $this->conditionB], 
                $this->getLeafEvaluator(True, False)
            )
        );
    }

    public function testAndEvaluatorReturnsNullWhenTruesAndNulls()
    {
        $this->assertNull(
            $this->conditionTreeEvaluator->evaluate(
                ['and', $this->conditionA, $this->conditionB, $this->conditionC], 
                $this->getLeafEvaluator(true, true, null)
            )
        );
    }
}
