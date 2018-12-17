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

    // Helper for mocking leaf evaluator with side effects.
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

    // Test that evaluate returns True when the leaf condition evaluator returns True.
    public function testEvaluateReturnsTrueWhenLeafConditionReturnsTrue()
    {
        $this->assertTrue(
            $this->conditionTreeEvaluator->evaluate($this->conditionA, $this->getLeafEvaluator(true))
        );
    }

    // Test that evaluate returns False when the leaf condition evaluator returns False.
    public function testEvaluateReturnsFalseWhenLeafConditionReturnsFalse()
    {
        $this->assertFalse(
            $this->conditionTreeEvaluator->evaluate($this->conditionA, $this->getLeafEvaluator(false))
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

    // Test that andEvaluator returns True when all conditions evaluate to True.
    public function testAndEvaluatorReturnsTrueWhenAllConditionsEvaluateTrue()
    {
        $this->assertTrue(
            $this->conditionTreeEvaluator->evaluate(
                ['and', $this->conditionA, $this->conditionB],
                $this->getLeafEvaluator(true, true)
            )
        );
    }

    //  Test that andEvaluator returns null when all operands evaluate to null.
    public function testAndEvaluatorReturnsNullWhenAllNulls()
    {
        $this->assertNull(
            $this->conditionTreeEvaluator->evaluate(
                ['and', $this->conditionA, $this->conditionB],
                $this->getLeafEvaluator(null, null)
            )
        );
    }

    // Test that andEvaluator returns null when operands evaluate to trues and null.
    public function testAndEvaluatorReturnsNullWhenTruesAndNull()
    {
        $this->assertNull(
            $this->conditionTreeEvaluator->evaluate(
                ['and', $this->conditionA, $this->conditionB, $this->conditionC],
                $this->getLeafEvaluator(true, true, null)
            )
        );

        $this->assertNull(
            $this->conditionTreeEvaluator->evaluate(
                ['and', $this->conditionA, $this->conditionB, $this->conditionC],
                $this->getLeafEvaluator(null, true, true)
            )
        );
    }

    //  Test that andEvaluator returns False when when operands evaluate to falses and null.
    public function testAndEvaluatorReturnsFalseWhenFalsesAndNull()
    {
        $this->assertFalse(
            $this->conditionTreeEvaluator->evaluate(
                ['and', $this->conditionA, $this->conditionB, $this->conditionC],
                $this->getLeafEvaluator(false, false, null)
            )
        );

        $this->assertFalse(
            $this->conditionTreeEvaluator->evaluate(
                ['and', $this->conditionA, $this->conditionB, $this->conditionC],
                $this->getLeafEvaluator(null, false, false)
            )
        );
    }

    // Test that andEvaluator returns False when operands evaluate to trues, falses and null.
    public function testAndEvaluatorReturnsFalseWhenTruesFalsesAndNull()
    {
        $this->assertFalse(
            $this->conditionTreeEvaluator->evaluate(
                ['and', $this->conditionA, $this->conditionB, $this->conditionC],
                $this->getLeafEvaluator(true, false, null)
            )
        );
    }

    // Test that orEvaluator returns True when any one condition evaluates to True.
    public function testOrEvaluatorReturnsTrueWhenAnyOneConditionEvaluatesTrue()
    {
        $this->assertTrue(
            $this->conditionTreeEvaluator->evaluate(
                ['or', $this->conditionA, $this->conditionB],
                $this->getLeafEvaluator(false, true)
            )
        );
    }

    // Test that orEvaluator returns False when all conditions evaluates to False.
    public function testOrEvaluatorReturnsFalseWhenAllConditionsAreFalse()
    {
        $this->assertFalse(
            $this->conditionTreeEvaluator->evaluate(
                ['or', $this->conditionA, $this->conditionB],
                $this->getLeafEvaluator(false, false)
            )
        );
    }

    // Test that orEvaluator returns null when all operands evaluate to null.
    public function testOrEvaluatorReturnsNullWhenAllNulls()
    {
        $this->assertNull(
            $this->conditionTreeEvaluator->evaluate(
                ['or', $this->conditionA, $this->conditionB],
                $this->getLeafEvaluator(null, null)
            )
        );
    }

    // Test that orEvaluator returns True when operands evaluate to trues and null.
    public function testOrEvaluatorReturnsTrueWhenTruesAndNull()
    {
        $this->assertTrue(
            $this->conditionTreeEvaluator->evaluate(
                ['or', $this->conditionA, $this->conditionB, $this->conditionC],
                $this->getLeafEvaluator(true, true, null)
            )
        );

        $this->assertTrue(
            $this->conditionTreeEvaluator->evaluate(
                ['or', $this->conditionA, $this->conditionB, $this->conditionC],
                $this->getLeafEvaluator(null, true, true)
            )
        );
    }

    // Test that orEvaluator returns null when operands evaluate to falses and null.
    public function testOrEvaluatorReturnsNullWhenFalsesAndNull()
    {
        $this->assertNull(
            $this->conditionTreeEvaluator->evaluate(
                ['or', $this->conditionA, $this->conditionB, $this->conditionC],
                $this->getLeafEvaluator(false, false, null)
            )
        );

        $this->assertNull(
            $this->conditionTreeEvaluator->evaluate(
                ['or', $this->conditionA, $this->conditionB, $this->conditionC],
                $this->getLeafEvaluator(null, false, false)
            )
        );
    }

    // Test that orEvaluator returns True when operands evaluate to trues, falses and null.
    public function testOrEvaluatorReturnsTrueWhenTruesFalsesAndNull()
    {
        $this->assertTrue(
            $this->conditionTreeEvaluator->evaluate(
                ['or', $this->conditionA, $this->conditionB, $this->conditionC],
                $this->getLeafEvaluator(false, null, true)
            )
        );
    }

    //  Test that notEvaluator returns True when condition evaluates to False.
    public function testNotEvaluatorReturnsTrueWhenConditionEvaluatesFalse()
    {
        $this->assertTrue(
            $this->conditionTreeEvaluator->evaluate(
                ['not', $this->conditionA],
                $this->getLeafEvaluator(false)
            )
        );
    }

    // Test that notEvaluator returns False when condition evaluates to True.
    public function testNotEvaluatorReturnsFalseWhenConditionEvaluatesTrue()
    {
        $this->assertFalse(
            $this->conditionTreeEvaluator->evaluate(
                ['not', $this->conditionA],
                $this->getLeafEvaluator(true)
            )
        );
    }

    // Test that notEvaluator negates first condition and ignores rest.
    public function testNotEvaluatorNegatesFirstConditionIgnoresRest()
    {
        $this->assertTrue(
            $this->conditionTreeEvaluator->evaluate(
                ['not', $this->conditionA, $this->conditionB, $this->conditionC],
                $this->getLeafEvaluator(false, true, null)
            )
        );

        $this->assertFalse(
            $this->conditionTreeEvaluator->evaluate(
                ['not', $this->conditionA, $this->conditionB, $this->conditionC],
                $this->getLeafEvaluator(true, false, null)
            )
        );

        $this->assertNull(
            $this->conditionTreeEvaluator->evaluate(
                ['not', $this->conditionA, $this->conditionB, $this->conditionC],
                $this->getLeafEvaluator(null, false, true)
            )
        );
    }

    // Test that notEvaluator returns null when condition evaluates to null.
    public function testNotEvaluatorReturnsNullWhenConditionEvaluatesNull()
    {
        $this->assertNull(
            $this->conditionTreeEvaluator->evaluate(
                ['not', $this->conditionA],
                $this->getLeafEvaluator(null)
            )
        );
    }

    // Test that notEvaluator returns null when there are no conditions.
    public function testNotEvaluatorReturnsNullWhenNoConditionsGiven()
    {
        $this->assertNull(
            $this->conditionTreeEvaluator->evaluate(
                ['not'],
                $this->getLeafEvaluator(null)
            )
        );
    }

    // Test that by default OR operator is assumed when the first item in conditions is not
    //    a recognized operator.
    public function testEvaluateAssumesOrOperatorWhenFirstArrayItemUnrecognizedOperator()
    {
        $this->assertTrue(
            $this->conditionTreeEvaluator->evaluate(
                [$this->conditionA, $this->conditionB],
                $this->getLeafEvaluator(false, true)
            )
        );

        $this->assertFalse(
            $this->conditionTreeEvaluator->evaluate(
                [$this->conditionA, $this->conditionB],
                $this->getLeafEvaluator(false, false)
            )
        );
    }

}
