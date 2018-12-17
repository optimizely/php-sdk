<?php
/**
 * Copyright 2016, 2018, Optimizely
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
use \stdClass;

class CustomAttributeConditionEvaluatorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->browserConditionSafari = (object)[
            'type' => 'custom_attribute',
            'name' => 'browser_type',
            'value' => 'safari',
            'match' => 'exact'
        ];
        $this->booleanCondition = (object)[
            'type' => 'custom_attribute',
            'name' => 'is_firefox',
            'value' => True,
            'match' => 'exact'
        ];
        $this->integerCondition = (object)[
            'type' => 'custom_attribute',
            'name' => 'num_users',
            'value' => 10,
            'match' => 'exact'
        ];
        $this->doubleCondition =  (object)[
            'type' => 'custom_attribute',
            'name' => 'pi_value',
            'value' => 3.14,
            'match' => 'exact'
        ];
        $this->existsCondition = (object)[
            'type' => 'custom_attribute',
            'name' => 'input_value',
            'value' => null,
            'match' => 'exists'
        ];
        $this->exactStringCondition = (object)[
            'name' => 'favorite_constellation',
            'value' =>'Lacerta',
            'type' => 'custom_attribute',
            'match' =>'exact'
        ];
        $this->exactIntCondition = (object)[
            'name' => 'lasers_count',
            'value' => 9000,
            'type' => 'custom_attribute',
            'match' => 'exact'
        ];
        $this->exactFloatCondition = (object)[
            'name' => 'lasers_count',
            'value' => 9000.0,
            'type' => 'custom_attribute',
            'match' => 'exact'
        ];
        $this->exactBoolCondition = (object)[
            'name' => 'did_register_user',
            'value' => False,
            'type' => 'custom_attribute',
            'match' => 'exact'
        ];
        $this->substringCondition = (object)[
            'name' => 'headline_text',
            'value' => 'buy now',
            'type' => 'custom_attribute',
            'match' => 'substring'
        ];
        $this->gtIntCondition = (object)[
            'name' => 'meters_travelled',
            'value' => 48,
            'type' => 'custom_attribute',
            'match' => 'gt'
        ];
        $this->gtFloatCondition = (object)[
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'gt'
        ];
        $this->ltIntCondition = (object)[
            'name' => 'meters_travelled',
             'value' => 48,
             'type' => 'custom_attribute',
             'match' => 'lt'
          ];
        $this->ltFloatCondition = (object)[
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'lt'
        ];
    }

    public function testEvaluateReturnsTrueWhenAttrsPassAudienceCondition()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['browser_type' => 'safari']
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->browserConditionSafari
            )
        );
    }

    public function testEvaluateReturnsFalseWhenAttrsFailAudienceConditions()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['browser_type' => 'chrome']
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->browserConditionSafari
            )
        );
    }

    public function testEvaluateForDifferentTypedAttributes()
    {
        $userAttributes = [
            'browser_type' => 'safari',
            'is_firefox' => True,
            'num_users' => 10,
            'pi_value' => 3.14
        ];

        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            $userAttributes
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->browserConditionSafari
            )
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->booleanCondition
            )
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->integerCondition
            )
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->doubleCondition
            )
        );
    }

    public function testEvaluateReturnsNullForInvalidMatchProperty()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['weird_condition' => 'hi']
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                (object)[
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
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation' => 'Lacerta']
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                (object)[
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
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['weird_condition' => 'hi']
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                (object)[
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
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                $this->existsCondition
            )
        );
    }

    public function testExistsReturnsFalseWhenUserProvidedValueIsNull()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['input_value' => null]
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                $this->existsCondition
            )
        );
    }

    public function testExistsReturnsTrueWhenUserProvidedValueIsString()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['input_value' => 'hi']
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                $this->existsCondition
            )
        );
    }

    public function testExistsReturnsTrueWhenUserProvidedValueIsNumber()
    {

        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['input_value' => 10]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                $this->existsCondition
            )
        );

        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['input_value' => 10.0]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                $this->existsCondition
            )
        );

    }

    public function testExistsReturnsTrueWhenUserProvidedValueIsBoolean()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['input_value' => False]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                $this->existsCondition
            )
        );
    }

    public function testExactStringReturnsTrueWhenAttrsEqualToConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation' => 'Lacerta']
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->exactStringCondition
            )
        );
    }

    public function testExactStringReturnsFalseWhenAttrsNotEqualToConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation' => 'The Big Dipper']
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->exactStringCondition
            )
        );
    }

    public function testExactStringReturnsNullWhenAttrsIsDifferentTypeFromConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation' => False]
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->exactStringCondition
            )
        );
    }

    public function testExactStringReturnsNullWhenNoUserProvidedValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                $this->exactStringCondition
            )
        );
    }

    public function testExactIntReturnsTrueWhenAttrsEqualToConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => 9000]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->exactIntCondition
            )
        );
    }

    public function testExactFloatReturnsTrueWhenAttrsEqualToConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => 9000.0]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->exactFloatCondition
            )
        );
    }

    public function testExactIntReturnsFalseWhenAttrsNotEqualToConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => 8000]
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->exactIntCondition
            )
        );
    }

    public function testExactFloatReturnsFalseWhenAttrsNotEqualToConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => 8000.0]
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->exactFloatCondition
            )
        );
    }

    public function testExactIntReturnsNullWhenAttrsIsDifferentTypeFromConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => 'hi']
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->exactIntCondition
            )
        );

        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => True]
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->exactIntCondition
            )
        );
    }

    public function testExactFloatReturnsNullWhenAttrsIsDifferentTypeFromConditionValue()
    {
      $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
          ['lasers_count' => 'hi']
      );

      $this->assertNull(
          $this->customAttrConditionEvaluator->evaluate(
                $this->exactFloatCondition
          )
      );

      $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
          ['lasers_count' => True]
      );

      $this->assertNull(
          $this->customAttrConditionEvaluator->evaluate(
                $this->exactFloatCondition
          )
      );
    }

    public function testExactIntReturnsNullWhenNoUserProvidedValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                $this->exactIntCondition
            )
        );
    }

    public function testExactFloatReturnsNullWhenNoUserProvidedValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                $this->exactFloatCondition
            )
        );
    }

    public function testExactBoolReturnsTrueWhenAttrsEqualToConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['did_register_user' => False]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->exactBoolCondition
            )
        );
    }

    public function testExactBoolReturnsFalseWhenAttrsNotEqualToConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['did_register_user' => True]
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->exactBoolCondition
            )
        );
    }

    public function testExactBoolReturnsNullWhenAttrsIsDifferentTypeFromConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['did_register_user' => 0]
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->exactBoolCondition
            )
        );
    }

    public function testExactBoolReturnsNullWhenWhenNoUserProvidedValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->exactBoolCondition
            )
        );
    }

    public function testSubstringReturnsTrueWhenConditionValueIsSubstringOfUserValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['headline_text' => 'Limited time, buy now!']
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->substringCondition
            )
        );
    }

    public function testSubstringReturnsFalseWhenConditionValueIsNotSubstringOfUserValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['headline_text' => 'Breaking news!']
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->substringCondition
            )
        );
    }

    public function testSubstringReturnsNullWhenUserProvidedvalueNotAString()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['headline_text' => 10]
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->substringCondition
            )
        );
    }

    public function testSubstringReturnsNullWhenNoUserProvidedValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->substringCondition
            )
        );
    }

    public function testGreaterThanIntReturnsTrueWhenUserValueGreaterThanConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48.1]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtIntCondition
            )
        );
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 49]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtIntCondition
            )
        );
    }

    public function testGreaterThanFloatReturnsTrueWhenUserValueGreaterThanConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48.3]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtFloatCondition
            )
        );
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 49]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtFloatCondition
            )
        );
    }

    public function testGreaterThanIntReturnsFalseWhenUserValueNotGreaterThanConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 47.9]
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtIntCondition
            )
        );
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 47]
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtIntCondition
            )
        );
    }

    public function testGreaterThanFloatReturnsFalseWhenUserValueNotGreaterThanConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48.2]
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtFloatCondition
            )
        );
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48]
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtFloatCondition
            )
        );
    }

    public function testGreaterThanIntReturnsNullWhenUserValueIsNotANumber()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 'a long way']
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtIntCondition
            )
        );
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => False]
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtIntCondition
            )
        );
    }

    public function testGreaterThanFloatReturnsNullWhenUserValueIsNotANumber()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 'a long way']
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtFloatCondition
            )
        );
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => False]
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtFloatCondition
            )
        );
    }

    public function testGreaterThanIntReturnsNullWhenNoUserProvidedValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtIntCondition
            )
        );
    }

    public function testGreaterThanFloatReturnsNullWhenNoUserProvidedValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 'a long way']
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->gtFloatCondition
            )
        );
    }

    public function testLessThanIntReturnsTrueWhenUserValueLessThanConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 47.9]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltIntCondition
            )
        );
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 47]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltIntCondition
            )
        );
    }

    public function testLessThanFloatReturnsTrueWhenUserValueLessThanConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48.1]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltFloatCondition
            )
        );
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48]
        );

        $this->assertTrue(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltFloatCondition
            )
        );
    }

    public function testLessThanIntReturnsFalseWhenUserValueNotLessThanConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48.1]
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltIntCondition
            )
        );
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 49]
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltIntCondition
            )
        );
    }

    public function testLessThanFloatReturnsFalseWhenUserValueNotLessThanConditionValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48.2]
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltFloatCondition
            )
        );
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 49]
        );

        $this->assertFalse(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltFloatCondition
            )
        );
    }

    public function testLessThanIntReturnsNullWhenUserValueIsNotANumber()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 'a long way']
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltIntCondition
            )
        );

        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => False]
        );
        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltIntCondition
            )
        );
    }

    public function testLessThanFloatReturnsNullWhenUserValueIsNotANumber()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 'a long way']
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltFloatCondition
            )
        );
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => False]
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltFloatCondition
            )
        );
    }

    public function testLessThanIntReturnsNullWhenNoUserProvidedValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            []
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltIntCondition
            )
        );
    }

    public function testLessThanFloatReturnsNullWhenNoUserProvidedValue()
    {
        $this->customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 'a long way']
        );

        $this->assertNull(
            $this->customAttrConditionEvaluator->evaluate(
                  $this->ltFloatCondition
            )
        );
    }
}
