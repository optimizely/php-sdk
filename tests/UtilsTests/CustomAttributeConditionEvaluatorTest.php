<?php
/**
 * Copyright 2018-2020, Optimizely
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
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();

        $this->browserConditionSafari = [
            'type' => 'custom_attribute',
            'name' => 'browser_type',
            'value' => 'safari',
            'match' => 'exact',
        ];
        $this->booleanCondition = [
            'type' => 'custom_attribute',
            'name' => 'is_firefox',
            'value' => true,
            'match' => 'exact',
        ];
        $this->integerCondition = [
            'type' => 'custom_attribute',
            'name' => 'num_users',
            'value' => 10,
            'match' => 'exact',
        ];
        $this->doubleCondition = [
            'type' => 'custom_attribute',
            'name' => 'pi_value',
            'value' => 3.14,
            'match' => 'exact',
        ];
        $this->existsCondition = [
            'type' => 'custom_attribute',
            'name' => 'input_value',
            'value' => null,
            'match' => 'exists',
        ];
        $this->exactStringCondition = [
            'name' => 'favorite_constellation',
            'value' => 'Lacerta',
            'type' => 'custom_attribute',
            'match' => 'exact',
        ];
        $this->exactIntCondition = [
            'name' => 'lasers_count',
            'value' => 9000,
            'type' => 'custom_attribute',
            'match' => 'exact',
        ];
        $this->exactFloatCondition = [
            'name' => 'lasers_count',
            'value' => 9000.0,
            'type' => 'custom_attribute',
            'match' => 'exact',
        ];
        $this->exactBoolCondition = [
            'name' => 'did_register_user',
            'value' => false,
            'type' => 'custom_attribute',
            'match' => 'exact',
        ];
        $this->substringCondition = [
            'name' => 'headline_text',
            'value' => 'buy now',
            'type' => 'custom_attribute',
            'match' => 'substring',
        ];
        $this->gtIntCondition = [
            'name' => 'meters_travelled',
            'value' => 48,
            'type' => 'custom_attribute',
            'match' => 'gt',
        ];
        $this->gtFloatCondition = [
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'gt',
        ];
        $this->ltIntCondition = [
            'name' => 'meters_travelled',
            'value' => 48,
            'type' => 'custom_attribute',
            'match' => 'lt',
        ];
        $this->ltFloatCondition = [
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'lt',
        ];
    }

    public function testEvaluateReturnsTrueWhenAttrsPassAudienceCondition()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['browser_type' => 'safari'],
            $this->loggerMock
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
            ['browser_type' => 'chrome'],
            $this->loggerMock
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
            'pi_value' => 3.14,
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            $userAttributes,
            $this->loggerMock
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
            ['weird_condition' => 'hi'],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                [
                    'type' => 'custom_attribute',
                    'name' => 'weird_condition',
                    'value' => 'hi',
                    'match' => 'weird_match',
                ]
            )
        );
    }

    public function testEvaluateAssumesExactWhenConditionMatchPropertyIsNull()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation' => 'Lacerta'],
            $this->loggerMock
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                [
                    'type' => 'custom_attribute',
                    'name' => 'favorite_constellation',
                    'value' => 'Lacerta',
                    'match' => null,
                ]
            )
        );
    }

    public function testEvaluateReturnsNullWhenConditionHasInvalidTypeProperty()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['weird_condition' => 'hi'],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                [
                    'type' => 'weird_type',
                    'name' => 'weird_condition',
                    'value' => 'hi',
                    'match' => 'exact',
                ]
            )
        );
    }

    public function testExistsReturnsFalseWhenNoUserProvidedValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            [],
            $this->loggerMock
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
            ['input_value' => null],
            $this->loggerMock
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
            ['input_value' => 'hi'],
            $this->loggerMock
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
            ['input_value' => 10],
            $this->loggerMock
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->existsCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['input_value' => 10.0],
            $this->loggerMock
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
            ['input_value' => false],
            $this->loggerMock
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
            ['favorite_constellation' => 'Lacerta'],
            $this->loggerMock
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
            ['favorite_constellation' => 'The Big Dipper'],
            $this->loggerMock
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
            ['favorite_constellation' => false],
            $this->loggerMock
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
            [],
            $this->loggerMock
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
            ['lasers_count' => 9000],
            $this->loggerMock
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
            ['lasers_count' => 9000.0],
            $this->loggerMock
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
            ['lasers_count' => 8000],
            $this->loggerMock
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
            ['lasers_count' => 8000.0],
            $this->loggerMock
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
            ['lasers_count' => 'hi'],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->exactIntCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => true],
            $this->loggerMock
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
            ['lasers_count' => 'hi'],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->exactFloatCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['lasers_count' => true],
            $this->loggerMock
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
            [],
            $this->loggerMock
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
            [],
            $this->loggerMock
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
            ['did_register_user' => false],
            $this->loggerMock
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
            ['did_register_user' => true],
            $this->loggerMock
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
            ['did_register_user' => 0],
            $this->loggerMock
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
            [],
            $this->loggerMock
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
            ['headline_text' => 'Limited time, buy now!'],
            $this->loggerMock
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
            ['headline_text' => 'Breaking news!'],
            $this->loggerMock
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
            ['headline_text' => 10],
            $this->loggerMock
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
            [],
            $this->loggerMock
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
            ['meters_travelled' => 48.1],
            $this->loggerMock
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->gtIntCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 49],
            $this->loggerMock
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
            ['meters_travelled' => 48.3],
            $this->loggerMock
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->gtFloatCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 49],
            $this->loggerMock
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
            ['meters_travelled' => 47.9],
            $this->loggerMock
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->gtIntCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 47],
            $this->loggerMock
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
            ['meters_travelled' => 48.2],
            $this->loggerMock
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->gtFloatCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48],
            $this->loggerMock
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
            ['meters_travelled' => 'a long way'],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->gtIntCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => false],
            $this->loggerMock
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
            ['meters_travelled' => 'a long way'],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->gtFloatCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => false],
            $this->loggerMock
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
            [],
            $this->loggerMock
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
            [],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->gtFloatCondition
            )
        );
    }

    public function testGreaterThanEqualToIntReturnsTrueWhenUserValueGreaterThanOrEqualToConditionValue()
    {
        $geIntCondition = [
            'name' => 'meters_travelled',
            'value' => 48,
            'type' => 'custom_attribute',
            'match' => 'ge',
        ];

        $testValues = [48.1, 49, 48];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['meters_travelled' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $geIntCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testGreaterThanEqualToFloatReturnsTrueWhenUserValueGreaterThanOrEqualToConditionValue()
    {
        $geFloatCondition = [
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'ge',
        ];

        $testValues = [48.3, 49, 48.2];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['meters_travelled' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $geFloatCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testGreaterThanEqualToIntReturnsFalseWhenUserValueLessThanConditionValue()
    {
        $geIntCondition = [
            'name' => 'meters_travelled',
            'value' => 48,
            'type' => 'custom_attribute',
            'match' => 'ge',
        ];

        $testValues = [47.9, 47];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['meters_travelled' => $val],
                $this->loggerMock
            );

            $this->assertFalse(
                $customAttrConditionEvaluator->evaluate(
                    $geIntCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testGreaterThanEqualToFloatReturnsFalseWhenUserValueLessThanConditionValue()
    {
        $geFloatCondition = [
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'ge',
        ];

        $testValues = [48.1, 48];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['meters_travelled' => $val],
                $this->loggerMock
            );

            $this->assertFalse(
                $customAttrConditionEvaluator->evaluate(
                    $geFloatCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testGreaterThanEqualToIntReturnsNullWhenUserValueIsNotANumber()
    {
        $geIntCondition = [
            'name' => 'meters_travelled',
            'value' => 48,
            'type' => 'custom_attribute',
            'match' => 'ge',
        ];

        $testValues = ['a long way', false];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['meters_travelled' => $val],
                $this->loggerMock
            );

            $this->assertNull(
                $customAttrConditionEvaluator->evaluate(
                    $geIntCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testGreaterThanEqualToFloatReturnsNullWhenUserValueIsNotANumber()
    {
        $geFloatCondition = [
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'ge',
        ];

        $testValues = ['a long way', false];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['meters_travelled' => $val],
                $this->loggerMock
            );

            $this->assertNull(
                $customAttrConditionEvaluator->evaluate(
                    $geFloatCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testGreaterThanEqualToIntReturnsNullWhenNoUserProvidedValue()
    {
        $geIntCondition = [
            'name' => 'meters_travelled',
            'value' => 48,
            'type' => 'custom_attribute',
            'match' => 'ge',
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            [],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $geIntCondition
            )
        );
    }

    public function testGreaterThanEqualToFloatReturnsNullWhenNoUserProvidedValue()
    {
        $geFloatCondition = [
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'ge',
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            [],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $geFloatCondition
            )
        );
    }

    public function testLessThanIntReturnsTrueWhenUserValueLessThanConditionValue()
    {
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 47.9],
            $this->loggerMock
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->ltIntCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 47],
            $this->loggerMock
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
            ['meters_travelled' => 48.1],
            $this->loggerMock
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $this->ltFloatCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 48],
            $this->loggerMock
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
            ['meters_travelled' => 48.1],
            $this->loggerMock
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->ltIntCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 49],
            $this->loggerMock
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
            ['meters_travelled' => 48.2],
            $this->loggerMock
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $this->ltFloatCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => 49],
            $this->loggerMock
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
            ['meters_travelled' => 'a long way'],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->ltIntCondition
            )
        );

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => false],
            $this->loggerMock
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
            ['meters_travelled' => 'a long way'],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->ltFloatCondition
            )
        );
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => false],
            $this->loggerMock
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
            [],
            $this->loggerMock
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
            [],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $this->ltFloatCondition
            )
        );
    }

    public function testLessThanEqualToIntReturnsTrueWhenUserValueLessThanOrEqualToConditionValue()
    {
        $leIntCondition = [
            'name' => 'meters_travelled',
            'value' => 48,
            'type' => 'custom_attribute',
            'match' => 'le',
        ];

        $testValues = [47.9, 47, 48];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['meters_travelled' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $leIntCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testLessThanEqualToFloatReturnsTrueWhenUserValueLessOrEqualToConditionValue()
    {
        $leFloatCondition = [
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'le',
        ];

        $testValues = [48.1, 48, 48.2];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['meters_travelled' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $leFloatCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testLessThanEqualToIntReturnsFalseWhenUserValueGreaterThanConditionValue()
    {
        $leIntCondition = [
            'name' => 'meters_travelled',
            'value' => 48,
            'type' => 'custom_attribute',
            'match' => 'le',
        ];

        $testValues = [48.1, 49];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['meters_travelled' => $val],
                $this->loggerMock
            );

            $this->assertFalse(
                $customAttrConditionEvaluator->evaluate(
                    $leIntCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testLessThanEqualToFloatReturnsFalseWhenUserValueGreaterThanConditionValue()
    {
        $leFloatCondition = [
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'le',
        ];

        $testValues = [48.3, 49];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['meters_travelled' => $val],
                $this->loggerMock
            );

            $this->assertFalse(
                $customAttrConditionEvaluator->evaluate(
                    $leFloatCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testLessThanEqualToIntReturnsNullWhenUserValueIsNotANumber()
    {
        $leIntCondition = [
            'name' => 'meters_travelled',
            'value' => 48,
            'type' => 'custom_attribute',
            'match' => 'le',
        ];

        $testValues = ['a long way', false];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['meters_travelled' => $val],
                $this->loggerMock
            );

            $this->assertNull(
                $customAttrConditionEvaluator->evaluate(
                    $leIntCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testLessThanEqualToFloatReturnsNullWhenUserValueIsNotANumber()
    {
        $leFloatCondition = [
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'le',
        ];

        $testValues = ['a long way', false];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['meters_travelled' => $val],
                $this->loggerMock
            );

            $this->assertNull(
                $customAttrConditionEvaluator->evaluate(
                    $leFloatCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testLessThanEqualToIntReturnsNullWhenNoUserProvidedValue()
    {
        $leIntCondition = [
            'name' => 'meters_travelled',
            'value' => 48,
            'type' => 'custom_attribute',
            'match' => 'le',
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            [],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $leIntCondition
            )
        );
    }

    public function testLessThanEqualToFloatReturnsNullWhenNoUserProvidedValue()
    {
        $leFloatCondition = [
            'name' => 'meters_travelled',
            'value' => 48.2,
            'type' => 'custom_attribute',
            'match' => 'le',
        ];
        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            [],
            $this->loggerMock
        );

        $this->assertNull(
            $customAttrConditionEvaluator->evaluate(
                $leFloatCondition
            )
        );
    }

    public function testSemVerGTMatcherReturnsFalseWhenAttributeValueIsLessThanOrEqualToConditionValue()
    {
        $semverGtCondition = [
            'name' => 'semversion_gt',
            'value' => "3.7.1",
            'type' => 'custom_attribute',
            'match' => 'semver_gt',
        ];

        $testValues = ["3.7.0", "3.7.1", "3.6", "2"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_gt' => $val],
                $this->loggerMock
            );

            $this->assertFalse(
                $customAttrConditionEvaluator->evaluate(
                    $semverGtCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerGTMatcherReturnsTrueWhenAttributeValueIsGreaterThanConditionValue()
    {
        $semverGtCondition = [
            'name' => 'semversion_gt',
            'value' => "3.7.1",
            'type' => 'custom_attribute',
            'match' => 'semver_gt',
        ];
        $testValues = ["3.7.2", "3.7.2-beta", "4.7.1", "3.8", "4"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_gt' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $semverGtCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerGTMatcherReturnsTrueWhenAttributeValueIsGreaterThanConditionValueBeta()
    {
        $semverGtCondition = [
            'name' => 'semversion_gt',
            'value' => "3.7.0-beta.2.3",
            'type' => 'custom_attribute',
            'match' => 'semver_gt',
        ];

        $testValues = ["3.7.0-beta.2.4", "3.7.0", "4.7.1", "3.8", "4"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_gt' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $semverGtCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerGEMatcherReturnsFalseWhenAttributeValueIsNotGreaterOrEqualToConditionValue()
    {
        $semverGeCondition = [
            'name' => 'semversion_ge',
            'value' => "3.7.1",
            'type' => 'custom_attribute',
            'match' => 'semver_ge',
        ];

        $testValues = ["3.7.0", "3.7.1-beta", "3.6", "2", "3"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_ge' => $val],
                $this->loggerMock
            );

            $this->assertFalse(
                $customAttrConditionEvaluator->evaluate(
                    $semverGeCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerGEMatcherReturnsTrueWhenAttributeValueIsGreaterOrEqualToConditionValue()
    {
        $semverGeCondition = [
            'name' => 'semversion_ge',
            'value' => "3.7.1",
            'type' => 'custom_attribute',
            'match' => 'semver_ge',
        ];

        $testValues = ["3.7.1", "3.7.2", "3.8.1", "4.7.1"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_ge' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $semverGeCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerGEMatcherReturnsTrueWhenAttributeValueIsGreaterOrEqualToConditionValueMajorOnly()
    {
        $semverGeCondition = [
            'name' => 'semversion_ge',
            'value' => "3",
            'type' => 'custom_attribute',
            'match' => 'semver_ge',
        ];

        $testValues = ["3.7.0", "3.0.0", "4.0"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_ge' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $semverGeCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerGEMatcherReturnsFalseWhenAttributeValueIsNotGreaterOrEqualToConditionValueMajorOnly()
    {
        $semverGeCondition = [
            'name' => 'semversion_ge',
            'value' => "3",
            'type' => 'custom_attribute',
            'match' => 'semver_ge',
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['semversion_ge' => "2"],
            $this->loggerMock
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $semverGeCondition
            )
        );
    }

    public function testSemVerGEMatcherReturnsTrueWhenAttributeValueIsGreaterOrEqualToConditionValueBeta()
    {
        $semverGeCondition = [
            'name' => 'semversion_ge',
            'value' => "3.7.0-beta.2.3",
            'type' => 'custom_attribute',
            'match' => 'semver_ge',
        ];

        $testValues = ["3.7.0-beta.2.3", "3.7.0-beta.2.4", "3.7.0-beta.2.3+1.2.3", "3.7.1-beta.2.3"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_ge' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $semverGeCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerLTMatcherReturnsFalseWhenAttributeValueIsGreaterThanOrEqualToConditionValue()
    {
        $semverLtCondition = [
            'name' => 'semversion_lt',
            'value' => "3.7.1",
            'type' => 'custom_attribute',
            'match' => 'semver_lt',
        ];

        $testValues = ["3.7.1", "3.7.2", "3.8", "4"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_lt' => $val],
                $this->loggerMock
            );

            $this->assertFalse(
                $customAttrConditionEvaluator->evaluate(
                    $semverLtCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerLTMatcherReturnsTrueWhenAttributeValueIsLessThanConditionValue()
    {
        $semverLtCondition = [
            'name' => 'semversion_lt',
            'value' => "3.7.1",
            'type' => 'custom_attribute',
            'match' => 'semver_lt',
        ];

        $testValues = ["3.7.0", "3.7.1-beta", "2.7.1", "3.7", "3"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_lt' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $semverLtCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerLTMatcherReturnsTrueWhenAttributeValueIsLessThanConditionValueBeta()
    {
        $semverLtCondition = [
            'name' => 'semversion_lt',
            'value' => "3.7.0-beta.2.3",
            'type' => 'custom_attribute',
            'match' => 'semver_lt',
        ];

        $testValues = ["3.7.0-beta.2.1", "3.7.0-beta"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_lt' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $semverLtCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerLEMatcherReturnsFalseWhenAttributeValueIsNotLessOrEqualToConditionValue()
    {
        $semverLeCondition = [
            'name' => 'semversion_le',
            'value' => "3.7.1",
            'type' => 'custom_attribute',
            'match' => 'semver_le',
        ];

        $testValues = ["3.7.2", "3.8", "4"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_le' => $val],
                $this->loggerMock
            );

            $this->assertFalse(
                $customAttrConditionEvaluator->evaluate(
                    $semverLeCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerLEMatcherReturnsTrueWhenAttributeValueIsLessOrEqualToConditionValue()
    {
        $semverLeCondition = [
            'name' => 'semversion_le',
            'value' => "3.7.1",
            'type' => 'custom_attribute',
            'match' => 'semver_le',
        ];

        $testValues = ["3.7.1", "3.7.0", "3.6.1", "2.7.1", "3.7.1-beta"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_le' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $semverLeCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerLEMatcherReturnsTrueWhenAttributeValueIsLessOrEqualToConditionValueMajorOnly()
    {
        $semverLeCondition = [
            'name' => 'semversion_le',
            'value' => "3",
            'type' => 'custom_attribute',
            'match' => 'semver_le',
        ];

        $testValues = ["3.7.0-beta.2.4", "3.0.0", "3.7.1-beta", "2.0"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_le' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $semverLeCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerLEMatcherReturnsFalseWhenAttributeValueIsNotLessOrEqualToConditionValueMajorOnly()
    {
        $semverLeCondition = [
            'name' => 'semversion_le',
            'value' => "3",
            'type' => 'custom_attribute',
            'match' => 'semver_le',
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['semversion_le' => "4"],
            $this->loggerMock
        );

        $this->assertFalse(
            $customAttrConditionEvaluator->evaluate(
                $semverLeCondition
            )
        );
    }

    public function testSemVerLEMatcherReturnsTrueWhenAttributeValueIsLessOrEqualToConditionValueBeta()
    {
        $semverLeCondition = [
            'name' => 'semversion_le',
            'value' => "3.7.0-beta.2.3",
            'type' => 'custom_attribute',
            'match' => 'semver_le',
        ];

        $testValues = ["3.7.0-beta.2.2", "3.7.0-beta.2.3", "3.7.0-beta.2.2+1.2.3", "3.6.1-beta.2.3+1.2"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_le' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $semverLeCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerEQMatcherReturnsFalseWhenAttributeValueIsNotEqualToConditionValue()
    {
        $semverEqCondition = [
            'name' => 'semversion_eq',
            'value' => "3.7.1",
            'type' => 'custom_attribute',
            'match' => 'semver_eq',
        ];

        $testValues = ["3.7.0", "3.7.2", "3.6", "2", "4", "3"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_eq' => $val],
                $this->loggerMock
            );

            $this->assertFalse(
                $customAttrConditionEvaluator->evaluate(
                    $semverEqCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerEQMatcherReturnsTrueWhenAttributeValueIsEqualToConditionValue()
    {
        $semverEqCondition = [
            'name' => 'semversion_eq',
            'value' => "3.7.1",
            'type' => 'custom_attribute',
            'match' => 'semver_eq',
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['semversion_eq' => "3.7.1"],
            $this->loggerMock
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $semverEqCondition
            )
        );
    }

    public function testSemVerEQMatcherReturnsTrueWhenAttributeValueIsEqualToConditionValueMajorOnly()
    {
        $semverEqCondition = [
            'name' => 'semversion_eq',
            'value' => "3",
            'type' => 'custom_attribute',
            'match' => 'semver_eq',
        ];

        $testValues = ["3.0.0", "3.1"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_eq' => $val],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $semverEqCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerEQMatcherReturnsFalseOrFalseWhenAttributeValueIsNotEqualToConditionValueMajorOnly()
    {
        $semverEqCondition = [
            'name' => 'semversion_eq',
            'value' => "3",
            'type' => 'custom_attribute',
            'match' => 'semver_eq',
        ];

        $testValues = ["4.0", "2"];

        foreach ($testValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_eq' => $val],
                $this->loggerMock
            );

            $this->assertFalse(
                $customAttrConditionEvaluator->evaluate(
                    $semverEqCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testSemVerEQMatcherReturnsTrueWhenAttributeValueIsEqualToConditionValueBeta()
    {
        $semverEqCondition = [
            'name' => 'semversion_eq',
            'value' => "3.7.0-beta.2.3",
            'type' => 'custom_attribute',
            'match' => 'semver_eq',
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['semversion_eq' => "3.7.0-beta.2.3"],
            $this->loggerMock
        );

        $this->assertTrue(
            $customAttrConditionEvaluator->evaluate(
                $semverEqCondition
            )
        );
    }

    public function testTargetComplex()
    {
        $targetVersions = ["2.1.3-beta+1", "2.1.3+build-1.2.3"];
        $userVersions = ["2.1.3-beta+1.2.3", "2.1.3+build-1"];
        $matchTypes = ["semver_gt", "semver_lt"];

        for ($i = 0; $i < count($targetVersions); $i++) {
            $semverCondition = [
                'name' => 'semversion',
                'value' => $targetVersions[$i],
                'type' => 'custom_attribute',
                'match' => $matchTypes[$i],
            ];

            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion' => $userVersions[$i]],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $semverCondition
                ),
                "Failed for user version: {$userVersions[$i]}"
            );
        }
    }

    public function testDifferentAttributeComplex()
    {
        $targetVersions = ["3.7.0", "3.7.0", "3.7.0-prerelease", "3.7.0-prerelease+build"];
        $userVersions = ["3.7.0+build", "3.7.0-prerelease", "3.7.0+build", "3.7.0-prerelease-prelrease+rc"];
        $matchTypes = ["semver_eq", "semver_lt", "semver_gt", "semver_gt"];

        for ($i = 0; $i < count($targetVersions); $i++) {
            $semverCondition = [
                'name' => 'semversion',
                'value' => $targetVersions[$i],
                'type' => 'custom_attribute',
                'match' => $matchTypes[$i],
            ];

            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion' => $userVersions[$i]],
                $this->loggerMock
            );

            $this->assertTrue(
                $customAttrConditionEvaluator->evaluate(
                    $semverCondition
                ),
                "Failed for user version: {$userVersions[$i]}"
            );
        }
    }

    public function testInvalidTargetVersionTypes()
    {
        $semverLeCondition = [
            'name' => 'semversion_le',
            'type' => 'custom_attribute',
            'match' => 'semver_le',
        ];

        $invalidTypeValues = [true, ["abc"], 10, ""];

        foreach ($invalidTypeValues as $val) {
            $semverLeCondition['value'] = $val;
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_le' => "3"],
                $this->loggerMock
            );
            $this->assertNull(
                $customAttrConditionEvaluator->evaluate(
                    $semverLeCondition
                ),
                "Failed for targeted version: {$val}"
            );
        }
    }

    public function testInvalidUserVersionTypes()
    {
        $semverLeCondition = [
            'name' => 'semversion_le',
            'value' => "3",
            'type' => 'custom_attribute',
            'match' => 'semver_le',
        ];

        $invalidTypeValues = [true, ["abc"], 10, ""];

        foreach ($invalidTypeValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_le' => $val],
                $this->loggerMock
            );
            $this->assertNull(
                $customAttrConditionEvaluator->evaluate(
                    $semverLeCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }

    public function testInvalidSemVersions()
    {
        $semverLeCondition = [
            'name' => 'semversion_le',
            'value' => "3",
            'type' => 'custom_attribute',
            'match' => 'semver_le',
        ];

        $invalidValues = ["-", ".", "..", "+", "+test", " ", "2 .3. 0", "2.",
            ".2.2", "3.7.2.2", "3.x", ",", "+build-prerelease"];

        foreach ($invalidValues as $val) {
            $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
                ['semversion_le' => $val],
                $this->loggerMock
            );
            $this->assertNull(
                $customAttrConditionEvaluator->evaluate(
                    $semverLeCondition
                ),
                "Failed for user version: {$val}"
            );
        }
    }
}
