<?php
/**
 * Copyright 2018-2019, Optimizely
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

use Optimizely\Utils\CustomAttributeConditionEvaluator;

class CustomAttributeConditionEvaluatorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->browserConditionSafari = [
            'type' => 'custom_attribute',
            'name' => 'browser_type',
            'value' => 'safari',
            'match' => 'exact'
        ];
        $this->booleanCondition = [
            'type' => 'custom_attribute',
            'name' => 'is_firefox',
            'value' => true,
            'match' => 'exact'
        ];
        $this->integerCondition = [
            'type' => 'custom_attribute',
            'name' => 'num_users',
            'value' => 10,
            'match' => 'exact'
        ];
        $this->doubleCondition =  [
            'type' => 'custom_attribute',
            'name' => 'pi_value',
            'value' => 3.14,
            'match' => 'exact'
        ];
        $this->existsCondition = [
            'type' => 'custom_attribute',
            'name' => 'input_value',
            'value' => null,
            'match' => 'exists'
        ];
        $this->exactStringCondition = [
            'name' => 'favorite_constellation',
            'value' =>'Lacerta',
            'type' => 'custom_attribute',
            'match' =>'exact'
        ];
        $this->exactIntCondition = [
            'name' => 'lasers_count',
            'value' => 9000,
            'type' => 'custom_attribute',
            'match' => 'exact'
        ];
        $this->exactFloatCondition = [
            'name' => 'lasers_count',
            'value' => 9000.0,
            'type' => 'custom_attribute',
            'match' => 'exact'
        ];
        $this->exactBoolCondition = [
            'name' => 'did_register_user',
            'value' => false,
            'type' => 'custom_attribute',
            'match' => 'exact'
        ];
        $this->substringCondition = [
            'name' => 'headline_text',
            'value' => 'buy now',
            'type' => 'custom_attribute',
            'match' => 'substring'
        ];
        $this->gtIntCondition = [
            'name' => 'meters_travelled',
            'value' => 48,
            'type' => 'custom_attribute',
            'match' => 'gt'
        ];
        $this->gtFloatCondition = [
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'gt'
        ];
        $this->ltIntCondition = [
            'name' => 'meters_travelled',
             'value' => 48,
             'type' => 'custom_attribute',
             'match' => 'lt'
          ];
        $this->ltFloatCondition = [
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'lt'
        ];
    }

    public function testEvaluateReturnsTrueWhenAttrsPassAudienceCondition()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['browser_type' => 'safari']
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->browserConditionSafari
            )
        );
    }

    public function testEvaluateReturnsFalseWhenAttrsFailAudienceConditions()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['browser_type' => 'chrome']
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->browserConditionSafari
            )
        );
    }

    public function testEvaluateForDifferentTypedAttributes()
    {
        $userAttributes = [
            'browser_type' => 'safari',
            'is_firefox' => true,
            'num_users' => 10,
            'pi_value' => 3.14
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            $userAttributes
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->browserConditionSafari
            )
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->booleanCondition
            )
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->integerCondition
            )
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->doubleCondition
            )
        );
    }

    public function testEvaluateReturnsNullForInvalidMatchProperty()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['weird_condition' => 'hi']
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                [
                    'type' => 'custom_attribute',
                    'name' => 'weird_condition',
                    'value' => 'hi',
                    'match' => 'weird_match'
                ]
            )
        );
    }

    public function testEvaluateAssumesExactWhenConditionMatchPropertyIsNull()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation' => 'Lacerta']
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                [
                    'type' => 'custom_attribute',
                    'name' => 'favorite_constellation',
                    'value' => 'Lacerta',
                    'match' => null
                ]
            )
        );
    }

    public function testEvaluateReturnsNullWhenConditionHasInvalidTypeProperty()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['weird_condition' => 'hi']
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                [
                    'type' => 'weird_type',
                    'name' => 'weird_condition',
                    'value' => 'hi',
                    'match' => 'exact'
                ]
            )
        );
    }

    public function testExistsReturnsFalseWhenNoUserProvidedValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->existsCondition
            )
        );
    }

    public function testExistsReturnsFalseWhenUserProvidedValueIsNull()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['input_value' => null]
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->existsCondition
            )
        );
    }

    public function testExistsReturnsTrueWhenUserProvidedValueIsString()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['input_value' => 'hi']
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->existsCondition
            )
        );
    }

    public function testExistsReturnsTrueWhenUserProvidedValueIsNumber()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['input_value' => 10]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->existsCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['input_value' => 10.0]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->existsCondition
            )
        );
    }

    public function testExistsReturnsTrueWhenUserProvidedValueIsBoolean()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['input_value' => false]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->existsCondition
            )
        );
    }

    public function testExactStringReturnsTrueWhenAttrsEqualToConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation' => 'Lacerta']
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->exactStringCondition
            )
        );
    }

    public function testExactStringReturnsFalseWhenAttrsNotEqualToConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation' => 'The Big Dipper']
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->exactStringCondition
            )
        );
    }

    public function testExactStringReturnsNullWhenAttrsIsDifferentTypeFromConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation' => false]
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->exactStringCondition
            )
        );
    }

    public function testExactStringReturnsNullWhenNoUserProvidedValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->exactStringCondition
            )
        );
    }

    public function testExactIntReturnsTrueWhenAttrsEqualToConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => 9000]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->exactIntCondition
            )
        );
    }

    public function testExactFloatReturnsTrueWhenAttrsEqualToConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => 9000.0]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->exactFloatCondition
            )
        );
    }

    public function testExactIntReturnsFalseWhenAttrsNotEqualToConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => 8000]
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->exactIntCondition
            )
        );
    }

    public function testExactFloatReturnsFalseWhenAttrsNotEqualToConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => 8000.0]
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->exactFloatCondition
            )
        );
    }

    public function testExactIntReturnsNullWhenAttrsIsDifferentTypeFromConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => 'hi']
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->exactIntCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => true]
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->exactIntCondition
            )
        );
    }

    public function testExactFloatReturnsNullWhenAttrsIsDifferentTypeFromConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => 'hi']
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->exactFloatCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => true]
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->exactFloatCondition
            )
        );
    }

    public function testExactIntReturnsNullWhenNoUserProvidedValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->exactIntCondition
            )
        );
    }

    public function testExactFloatReturnsNullWhenNoUserProvidedValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->exactFloatCondition
            )
        );
    }

    public function testExactBoolReturnsTrueWhenAttrsEqualToConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['did_register_user' => false]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->exactBoolCondition
            )
        );
    }

    public function testExactBoolReturnsFalseWhenAttrsNotEqualToConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['did_register_user' => true]
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->exactBoolCondition
            )
        );
    }

    public function testExactBoolReturnsNullWhenAttrsIsDifferentTypeFromConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['did_register_user' => 0]
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->exactBoolCondition
            )
        );
    }

    public function testExactBoolReturnsNullWhenWhenNoUserProvidedValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->exactBoolCondition
            )
        );
    }

    public function testSubstringReturnsTrueWhenConditionValueIsSubstringOfUserValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['headline_text' => 'Limited time, buy now!']
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->substringCondition
            )
        );
    }

    public function testSubstringReturnsFalseWhenConditionValueIsNotSubstringOfUserValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['headline_text' => 'Breaking news!']
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->substringCondition
            )
        );
    }

    public function testSubstringReturnsNullWhenUserProvidedvalueNotAString()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['headline_text' => 10]
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->substringCondition
            )
        );
    }

    public function testSubstringReturnsNullWhenNoUserProvidedValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->substringCondition
            )
        );
    }

    public function testGreaterThanIntReturnsTrueWhenUserValueGreaterThanConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48.1]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->gtIntCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 49]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->gtIntCondition
            )
        );
    }

    public function testGreaterThanFloatReturnsTrueWhenUserValueGreaterThanConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48.3]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->gtFloatCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 49]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->gtFloatCondition
            )
        );
    }

    public function testGreaterThanIntReturnsFalseWhenUserValueNotGreaterThanConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 47.9]
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->gtIntCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 47]
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->gtIntCondition
            )
        );
    }

    public function testGreaterThanFloatReturnsFalseWhenUserValueNotGreaterThanConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48.2]
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->gtFloatCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48]
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->gtFloatCondition
            )
        );
    }

    public function testGreaterThanIntReturnsNullWhenUserValueIsNotANumber()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 'a long way']
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->gtIntCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => false]
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->gtIntCondition
            )
        );
    }

    public function testGreaterThanFloatReturnsNullWhenUserValueIsNotANumber()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 'a long way']
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->gtFloatCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => false]
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->gtFloatCondition
            )
        );
    }

    public function testGreaterThanIntReturnsNullWhenNoUserProvidedValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->gtIntCondition
            )
        );
    }

    public function testGreaterThanFloatReturnsNullWhenNoUserProvidedValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->gtFloatCondition
            )
        );
    }

    public function testLessThanIntReturnsTrueWhenUserValueLessThanConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 47.9]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->ltIntCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 47]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->ltIntCondition
            )
        );
    }

    public function testLessThanFloatReturnsTrueWhenUserValueLessThanConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48.1]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->ltFloatCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48]
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->ltFloatCondition
            )
        );
    }

    public function testLessThanIntReturnsFalseWhenUserValueNotLessThanConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48.1]
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->ltIntCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 49]
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->ltIntCondition
            )
        );
    }

    public function testLessThanFloatReturnsFalseWhenUserValueNotLessThanConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48.2]
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->ltFloatCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 49]
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->ltFloatCondition
            )
        );
    }

    public function testLessThanIntReturnsNullWhenUserValueIsNotANumber()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 'a long way']
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->ltIntCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => false]
        );
        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->ltIntCondition
            )
        );
    }

    public function testLessThanFloatReturnsNullWhenUserValueIsNotANumber()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 'a long way']
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->ltFloatCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => false]
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->ltFloatCondition
            )
        );
    }

    public function testLessThanIntReturnsNullWhenNoUserProvidedValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->ltIntCondition
            )
        );
    }

    public function testLessThanFloatReturnsNullWhenNoUserProvidedValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->ltFloatCondition
            )
        );
    }
}
