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

use Optimizely\Utils\ConditionDecoder;
use Optimizely\Utils\ConditionEvaluator;

class ConditionEvaluatorTest extends \PHPUnit_Framework_TestCase
{
    private $conditionsList;
    private $conditionEvaluator;

    public function setUp()
    {
        $decoder = new ConditionDecoder();
        $conditions = "[\"and\", [\"or\", [\"or\", {\"name\": \"device_type\", \"type\": \"custom_attribute\", \"value\": \"iPhone\"}]], [\"or\", [\"or\", {\"name\": \"location\", \"type\": \"custom_attribute\", \"value\": \"San Francisco\"}]], [\"or\", [\"not\", [\"or\", {\"name\": \"browser\", \"type\": \"custom_attribute\", \"value\": \"Firefox\"}]]]]";
        $decoder->deserializeAudienceConditions($conditions);

        $this->conditionsList = $decoder->getConditionsList();
        $this->conditionEvaluator = new ConditionEvaluator();

        $this->conditionEvaluatorMock = $this->getMockBuilder(ConditionEvaluator::class)
            ->setMethods(array('evaluate'))
            ->getMock();
    }


    public function testGetSupportedOperators()
    {
        $this->assertEquals(
            ['and', 'or', 'not'],
            $this->conditionEvaluator->getSupportedOperators()
        );
    }

    public function testGetMatchTypes()
    {
        $this->assertEquals(
            ['exact', 'exists', 'gt', 'lt', 'substring'],
            $this->conditionEvaluator->getMatchtypes()
        );
    }

    public function testGetEvaluatorByMatchType()
    {
        $this->assertSame('exactEvaluator', $this->conditionEvaluator->getEvaluatorByMatchType('exact'));
        $this->assertSame('existsEvaluator', $this->conditionEvaluator->getEvaluatorByMatchType('exists'));
        $this->assertSame('greaterThanEvaluator', $this->conditionEvaluator->getEvaluatorByMatchType('gt'));
        $this->assertSame('lessThanEvaluator', $this->conditionEvaluator->getEvaluatorByMatchType('lt'));
        $this->assertSame('substringEvaluator', $this->conditionEvaluator->getEvaluatorByMatchType('substring'));
    }

    public function testAndEvaluatorWithAllTrue()
    {
        $this->conditionEvaluatorMock->expects($this->exactly(3))
            ->method('evaluate')
            ->will($this->onConsecutiveCalls(true, true, true));

        $this->assertTrue($this->conditionEvaluatorMock->andEvaluator(range(0,2), null));
    }

    public function testAndEvaluatorWithSingleFalse()
    {
        $this->conditionEvaluatorMock->expects($this->exactly(3))
            ->method('evaluate')
            ->will($this->onConsecutiveCalls(true, null, false));

        $this->assertFalse($this->conditionEvaluatorMock->andEvaluator(range(0,2), null));
    }

    public function testAndEvaluatorWithTrueAndNull()
    {
        $this->conditionEvaluatorMock->expects($this->exactly(3))
            ->method('evaluate')
            ->will($this->onConsecutiveCalls(true, true, null));

        $this->assertNull($this->conditionEvaluatorMock->andEvaluator(range(0,2), null));
    }

    public function testOrEvaluatorWithAllFalse()
    {
        $this->conditionEvaluatorMock->expects($this->exactly(3))
            ->method('evaluate')
            ->will($this->onConsecutiveCalls(false, false, false));

        $this->assertFalse($this->conditionEvaluatorMock->orEvaluator(range(0,2), null));
    }

    public function testOrEvaluatorWithSingleTrue()
    {
        $this->conditionEvaluatorMock->expects($this->exactly(3))
            ->method('evaluate')
            ->will($this->onConsecutiveCalls(false, null, true));

        $this->assertTrue($this->conditionEvaluatorMock->orEvaluator(range(0,2), null));
    }

    public function testOrEvaluatorWithFalseAndNull()
    {
        $this->conditionEvaluatorMock->expects($this->exactly(3))
            ->method('evaluate')
            ->will($this->onConsecutiveCalls(false, false, null));

        $this->assertNull($this->conditionEvaluatorMock->orEvaluator(range(0,2), null));
    }

    public function testNotEvaluatorWithNull()
    {
        $this->conditionEvaluatorMock->expects($this->exactly(1))
            ->method('evaluate')
            ->willReturn(null);

        $this->assertNull($this->conditionEvaluatorMock->notEvaluator(1, null));
    }

    public function testNotEvaluatorWithTrue()
    {
        $this->conditionEvaluatorMock->expects($this->exactly(1))
            ->method('evaluate')
            ->willReturn(true);

        $this->assertFalse($this->conditionEvaluatorMock->notEvaluator(1, null));
    }

    public function testNotEvaluatorWithFalse()
    {
        $this->conditionEvaluatorMock->expects($this->exactly(1))
            ->method('evaluate')
            ->willReturn(false);

        $this->assertTrue($this->conditionEvaluatorMock->notEvaluator(1, null));
    }

    public function testIsFinite()
    {
        $this->assertTrue($this->conditionEvaluator->isFinite(5));
        $this->assertTrue($this->conditionEvaluator->isFinite(5.5));

        $this->assertFalse($this->conditionEvaluator->isFinite('5'));
        $this->assertFalse($this->conditionEvaluator->isFinite(INF));
        $this->assertFalse($this->conditionEvaluator->isFinite(-INF));
        $this->assertFalse($this->conditionEvaluator->isFinite(NAN));
        $this->assertFalse($this->conditionEvaluator->isFinite(True));
        $this->assertFalse($this->conditionEvaluator->isFinite([]));
        $this->assertFalse($this->conditionEvaluator->isFinite(null));
    }

    public function testIsValueValidForExactConditions()
    {
        $this->assertTrue($this->conditionEvaluator->IsValueValidForExactConditions(5));
        $this->assertTrue($this->conditionEvaluator->IsValueValidForExactConditions('abc'));
        $this->assertTrue($this->conditionEvaluator->IsValueValidForExactConditions(false));

        $this->assertFalse($this->conditionEvaluator->IsValueValidForExactConditions(INF));
        $this->assertFalse($this->conditionEvaluator->IsValueValidForExactConditions(null));
        $this->assertFalse($this->conditionEvaluator->IsValueValidForExactConditions([]));
    }

    public function testExactEvaluator()
    {
        // should return null when user attribute value is invalid
        $condition = (object) ['name' => 'device_type', 'value' => 'android'];
        $userAttributes = ['device_type' => array()]; 
        $this->assertNull($this->conditionEvaluator->exactEvaluator($condition, $userAttributes));

        // should return null when condition value is invalid
        $condition = (object) ['name' => 'device_type', 'value' => array()];
        $userAttributes = ['device_type' => 'android']; 
        $this->assertNull($this->conditionEvaluator->exactEvaluator($condition, $userAttributes));

        // should return null when user attribute value and condition value have a type mismatch
        $condition = (object) ['name' => 'device_type', 'value' => 5];
        $userAttributes = ['device_type' => 5.0]; 
        $this->assertNull($this->conditionEvaluator->exactEvaluator($condition, $userAttributes));

        // should return null when respective user attribute value isn't present
        $condition = (object) ['name' => 'device_type', 'value' => 'android'];
        $userAttributes = []; 
        $this->assertNull($this->conditionEvaluator->exactEvaluator($condition, $userAttributes));

        // should return true when values are strictly equal
        $condition = (object) ['name' => 'device_type', 'value' => 'android'];
        $userAttributes = ['device_type' => 'android']; 
        $this->assertTrue($this->conditionEvaluator->exactEvaluator($condition, $userAttributes));

        // should return false when values are not equal
        $condition = (object) ['name' => 'device_type', 'value' => 'android'];
        $userAttributes = ['device_type' => 'ios']; 
        $this->assertFalse($this->conditionEvaluator->exactEvaluator($condition, $userAttributes));
    }

    public function testExistsEvaluator()
    {
        // should return false when attribute doesn't exist
        $condition = (object) ['name' => 'device_type', 'value' => 'android'];
        $userAttributes = []; 
        $this->assertFalse($this->conditionEvaluator->existsEvaluator($condition, $userAttributes));

        // should return false when attribute exists but is set to null
        $condition = (object) ['name' => 'device_type', 'value' => 'android'];
        $userAttributes =  ['device_type' => null];  
        $this->assertFalse($this->conditionEvaluator->existsEvaluator($condition, $userAttributes));

        // should return true when attribute has a non-null value with value type mismatch
        $condition = (object) ['name' => 'device_type', 'value' => 'android'];
        $userAttributes =  ['device_type' => false];  
        $this->assertTrue($this->conditionEvaluator->existsEvaluator($condition, $userAttributes));

        // should return true when attribute has a non-null value with value mismatch
        $condition = (object) ['name' => 'device_type', 'value' => 'android'];
        $userAttributes =  ['device_type' => 'ios'];  
        $this->assertTrue($this->conditionEvaluator->existsEvaluator($condition, $userAttributes));
    }

    public function testGreaterThanEvaluator()
    {
        // should return null when user attribute value is not finite
        $condition = (object) ['name' => 'device_type', 'value' => 5];
        $userAttributes = ['device_type' => '5']; 
        $this->assertNull($this->conditionEvaluator->greaterThanEvaluator($condition, $userAttributes)); 

        // should return null when condition value is not finite
        $condition = (object) ['name' => 'device_type', 'value' => '5'];
        $userAttributes = ['device_type' => 5]; 
        $this->assertNull($this->conditionEvaluator->greaterThanEvaluator($condition, $userAttributes));

        // should return true when user attribute value is > condition value
        $condition = (object) ['name' => 'device_type', 'value' => 5.0];
        $userAttributes = ['device_type' => 5.1]; 
        $this->assertTrue($this->conditionEvaluator->greaterThanEvaluator($condition, $userAttributes));

        // should return false when user attribute value is < condition value
        $condition = (object) ['name' => 'device_type', 'value' => 5.1];
        $userAttributes = ['device_type' => 5.0]; 
        $this->assertFalse($this->conditionEvaluator->greaterThanEvaluator($condition, $userAttributes));
    }

    public function testLessThanEvaluator()
    {
        // should return null when user attribute value is not finite
        $condition = (object) ['name' => 'device_type', 'value' => 5];
        $userAttributes = ['device_type' => '5']; 
        $this->assertNull($this->conditionEvaluator->lessThanEvaluator($condition, $userAttributes)); 

        // should return null when condition value is not finite
        $condition = (object) ['name' => 'device_type', 'value' => '5'];
        $userAttributes = ['device_type' => 5]; 
        $this->assertNull($this->conditionEvaluator->lessThanEvaluator($condition, $userAttributes));

        // should return true when user attribute value is < condition value
        $condition = (object) ['name' => 'device_type', 'value' => 5.1];
        $userAttributes = ['device_type' => 5.0]; 
        $this->assertTrue($this->conditionEvaluator->lessThanEvaluator($condition, $userAttributes));

        // should return false when user attribute value is > condition value
        $condition = (object) ['name' => 'device_type', 'value' => 5.0];
        $userAttributes = ['device_type' => 5.1]; 
        $this->assertFalse($this->conditionEvaluator->lessThanEvaluator($condition, $userAttributes));
    }

    public function testSubStringEvaluator()
    {
        // should return null when user attribute value is not a string
        $condition = (object) ['name' => 'device_type', 'value' => 'android'];
        $userAttributes = ['device_type' => 5]; 
        $this->assertNull($this->conditionEvaluator->substringEvaluator($condition, $userAttributes));

        // should return null when condition value is not a string
        $condition = (object) ['name' => 'device_type', 'value' => true];
        $userAttributes = ['device_type' => 'ios']; 
        $this->assertNull($this->conditionEvaluator->substringEvaluator($condition, $userAttributes));

        // should return true when condition value is a substring of user attribute value
        $condition = (object) ['name' => 'device_type', 'value' => 'android'];
        $userAttributes = ['device_type' => 'iloveandroid']; 
        $this->assertTrue($this->conditionEvaluator->substringEvaluator($condition, $userAttributes));

        // should return false when condition value is a not a substring of user attribute value
        $condition = (object) ['name' => 'device_type', 'value' => 'android'];
        $userAttributes = ['device_type' => 'iloveios']; 
        $this->assertFalse($this->conditionEvaluator->substringEvaluator($condition, $userAttributes));
    }

    public function testEvaluateConditionsMatch()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'San Francisco',
            'browser' => 'Chrome'
        ];

        $this->assertTrue($this->conditionEvaluator->evaluate($this->conditionsList, $userAttributes));
    }


    public function testEvaluateConditionsDoNotMatch()
    {
        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'San Francisco',
            'browser' => 'Firefox'
        ];

        $this->assertFalse($this->conditionEvaluator->evaluate($this->conditionsList, $userAttributes));
    }
}
