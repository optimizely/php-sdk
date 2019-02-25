<?php
/**
 * Copyright 2019, Optimizely
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
use Optimizely\Entity\Audience;
use Optimizely\Enums\AudienceEvaluationLogs;
use Optimizely\Utils\CustomAttributeConditionEvaluator;

class CustomAttributeConditionEvaluatorLoggingTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();
    }

    public function testEvaluateMatchTypeInvalid()
    {
        $logLevel = Logger::WARNING;
        $conditionList = [
          'name' => 'favorite_constellation',
          'value' => 'Lacerta',
          'type' => 'custom_attribute',
          'match' => 'regex'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            [],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                        ->method('log')
                        ->with(
                            $logLevel,
                            "Audience condition \"{\"name\":\"favorite_constellation\",\"value\":\"Lacerta\",\"type\":\"custom_attribute\",\"match\":\"regex\"}\" uses an unknown match type. You may need to upgrade to a newer release of the Optimizely SDK."
                        );

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testEvaluateConditionTypeInvalid()
    {
        $logLevel = Logger::WARNING;
        $conditionList = [
          'name' => 'favorite_constellation',
          'value' => 'Lacerta',
          'type' => 'sdk_version',
          'match' => 'exact'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            [],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                        ->method('log')
                        ->with(
                            $logLevel,
                            "Audience condition \"{\"name\":\"favorite_constellation\",\"value\":\"Lacerta\",\"type\":\"sdk_version\",\"match\":\"exact\"}\" uses an unknown condition type. You may need to upgrade to anewer release of the Optimizely SDK."
                        );

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testExactConditionValueUnknown()
    {
        $logLevel = Logger::WARNING;
        $conditionList = [
            'name' => 'favorite_constellation',
            'value' => pow(2, 53) + 1,
            'type' => 'custom_attribute',
            'match' => 'exact'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation'=> 9000],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(
                $logLevel,
                "Audience condition \"{\"name\":\"favorite_constellation\",\"value\":9007199254740993,\"type\":\"custom_attribute\",\"match\":\"exact\"}\" has an unsupported condition value. You may need to upgrade to a newer release of the Optimizely SDK."
            );

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testExactUserValueMissing()
    {
        $logLevel = Logger::DEBUG;
        $conditionList = [
          'name' => 'favorite_constellation',
          'value' => 'Lacerta',
          'type' => 'custom_attribute',
          'match' => 'exact'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            [],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                         ->method('log')
                         ->with($logLevel, "Audience condition {\"name\":\"favorite_constellation\",\"value\":\"Lacerta\",\"type\":\"custom_attribute\",\"match\":\"exact\"} evaluated to UNKNOWN because no value was passed for user attribute \"favorite_constellation\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testExactUserValueNull()
    {
        $logLevel = Logger::WARNING;
        $conditionList = [
          'name' => 'favorite_constellation',
          'value' => 'Lacerta',
          'type' => 'custom_attribute',
          'match' => 'exact'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation' => null],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                         ->method('log')
                         ->with($logLevel, "Audience condition \"{\"name\":\"favorite_constellation\",\"value\":\"Lacerta\",\"type\":\"custom_attribute\",\"match\":\"exact\"}\" evaluated to UNKNOWN because a null value was passed for user attribute \"favorite_constellation\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testGreaterThanConditionValueUnknown()
    {
        $logLevel = Logger::WARNING;
        $conditionList = [
            'name' => 'meters_travelled',
            'value' => pow(2, 53) + 1,
            'type' => 'custom_attribute',
            'match' => 'gt'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled'=> 9000],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with($logLevel, "Audience condition \"{\"name\":\"meters_travelled\",\"value\":9007199254740993,\"type\":\"custom_attribute\",\"match\":\"gt\"}\" has an unsupported condition value. You may need to upgrade to a newer release of the Optimizely SDK.");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testGreaterThanUserValueMissing()
    {
        $logLevel = Logger::DEBUG;
        $conditionList = [
          'name' => 'meters_travelled',
          'value' => 48,
          'type' => 'custom_attribute',
          'match' => 'gt'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            [],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                         ->method('log')
                         ->with($logLevel, "Audience condition {\"name\":\"meters_travelled\",\"value\":48,\"type\":\"custom_attribute\",\"match\":\"gt\"} evaluated to UNKNOWN because no value was passed for user attribute \"meters_travelled\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testGreaterThanUserValueNull()
    {
        $logLevel = Logger::WARNING;
        $conditionList = [
          'name' => 'meters_travelled',
          'value' => 48,
          'type' => 'custom_attribute',
          'match' => 'gt'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => null],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                         ->method('log')
                         ->with($logLevel, "Audience condition \"{\"name\":\"meters_travelled\",\"value\":48,\"type\":\"custom_attribute\",\"match\":\"gt\"}\" evaluated to UNKNOWN because a null value was passed for user attribute \"meters_travelled\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testLessThanConditionValueUnknown()
    {
        $logLevel = Logger::WARNING;
        $conditionList = [
            'name' => 'meters_travelled',
            'value' => pow(2, 53) + 1,
            'type' => 'custom_attribute',
            'match' => 'lt'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled'=> 48],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with($logLevel, "Audience condition \"{\"name\":\"meters_travelled\",\"value\":9007199254740993,\"type\":\"custom_attribute\",\"match\":\"lt\"}\" has an unsupported condition value. You may need to upgrade to a newer release of the Optimizely SDK.");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testLessThanUserValueMissing()
    {
        $logLevel = Logger::DEBUG;
        $conditionList = [
          'name' => 'meters_travelled',
          'value' => 48,
          'type' => 'custom_attribute',
          'match' => 'lt'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            [],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                         ->method('log')
                         ->with($logLevel, "Audience condition {\"name\":\"meters_travelled\",\"value\":48,\"type\":\"custom_attribute\",\"match\":\"lt\"} evaluated to UNKNOWN because no value was passed for user attribute \"meters_travelled\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testLessThanUserValueNull()
    {
        $logLevel = Logger::WARNING;
        $conditionList = [
          'name' => 'meters_travelled',
          'value' => 48,
          'type' => 'custom_attribute',
          'match' => 'lt'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => null],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                         ->method('log')
                         ->with($logLevel, "Audience condition \"{\"name\":\"meters_travelled\",\"value\":48,\"type\":\"custom_attribute\",\"match\":\"lt\"}\" evaluated to UNKNOWN because a null value was passed for user attribute \"meters_travelled\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testSubstringConditionValueUnknown()
    {
        $logLevel = Logger::WARNING;
        $conditionList = [
            'name' => 'headline_text',
            'value' => 900,
            'type' => 'custom_attribute',
            'match' => 'substring'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['headline_text'=> 'buy now'],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with($logLevel, "Audience condition \"{\"name\":\"headline_text\",\"value\":900,\"type\":\"custom_attribute\",\"match\":\"substring\"}\" has an unsupported condition value. You may need to upgrade to a newer release of the Optimizely SDK.");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testSubstringUserValueMissing()
    {
        $logLevel = Logger::DEBUG;
        $conditionList = [
          'name' => 'headline_text',
          'value' => 'buy now',
          'type' => 'custom_attribute',
          'match' => 'substring'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            [],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                         ->method('log')
                         ->with($logLevel, "Audience condition {\"name\":\"headline_text\",\"value\":\"buy now\",\"type\":\"custom_attribute\",\"match\":\"substring\"} evaluated to UNKNOWN because no value was passed for user attribute \"headline_text\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testSubstringUserValueNull()
    {
        $logLevel = Logger::WARNING;
        $conditionList = [
          'name' => 'headline_text',
          'value' => 'buy now',
          'type' => 'custom_attribute',
          'match' => 'substring'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['headline_text' => null],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                         ->method('log')
                         ->with($logLevel, "Audience condition \"{\"name\":\"headline_text\",\"value\":\"buy now\",\"type\":\"custom_attribute\",\"match\":\"substring\"}\" evaluated to UNKNOWN because a null value was passed for user attribute \"headline_text\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testExistsUserValueMissing()
    {
        $logLevel = Logger::WARNING;
        $conditionList = [
          'name' => 'input_value',
          'value' => null,
          'type' => 'custom_attribute',
          'match' => 'exists'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            [],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->never())
                         ->method('log');

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testExactUserValueInfinite()
    {
        $logLevel = Logger::DEBUG;
        $conditionList = [
            'name' => 'favorite_constellation',
            'value' => 900,
            'type' => 'custom_attribute',
            'match' => 'exact'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation' => pow(2, 53) + 1],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with($logLevel, "Audience condition {\"name\":\"favorite_constellation\",\"value\":900,\"type\":\"custom_attribute\",\"match\":\"exact\"} evaluated to UNKNOWN for user attribute \"favorite_constellation\" is not in the range [-2^53, +2^53].");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testExactUserValueUnexpectedType()
    {
        $logLevel = Logger::DEBUG;
        $conditionList = [
          'name' => 'favorite_constellation',
          'value' => 'Lacerta',
          'type' => 'custom_attribute',
          'match' => 'exact'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation' => []],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                         ->method('log')
                         ->with($logLevel, "Audience condition {\"name\":\"favorite_constellation\",\"value\":\"Lacerta\",\"type\":\"custom_attribute\",\"match\":\"exact\"} evaluated to UNKNOWN because a value of type \"array\" was passed for user attribute \"favorite_constellation\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testGreaterThanUserValueInfinite()
    {
        $logLevel = Logger::DEBUG;
        $conditionList = [
            'name' => 'meters_travelled',
            'value' => 900,
            'type' => 'custom_attribute',
            'match' => 'gt'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => pow(2, 53) + 1],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with($logLevel, "Audience condition {\"name\":\"meters_travelled\",\"value\":900,\"type\":\"custom_attribute\",\"match\":\"gt\"} evaluated to UNKNOWN for user attribute \"meters_travelled\" is not in the range [-2^53, +2^53].");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testGreaterThanUserValueUnexpectedType()
    {
        $logLevel = Logger::DEBUG;
        $conditionList = [
          'name' => 'meters_travelled',
          'value' => 48,
          'type' => 'custom_attribute',
          'match' => 'gt'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => '48'],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                         ->method('log')
                         ->with($logLevel, "Audience condition {\"name\":\"meters_travelled\",\"value\":48,\"type\":\"custom_attribute\",\"match\":\"gt\"} evaluated to UNKNOWN because a value of type \"string\" was passed for user attribute \"meters_travelled\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testLessThanUserValueInfinite()
    {
        $logLevel = Logger::DEBUG;
        $conditionList = [
            'name' => 'meters_travelled',
            'value' => 900,
            'type' => 'custom_attribute',
            'match' => 'lt'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => pow(2, 53) + 1],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with($logLevel, "Audience condition {\"name\":\"meters_travelled\",\"value\":900,\"type\":\"custom_attribute\",\"match\":\"lt\"} evaluated to UNKNOWN for user attribute \"meters_travelled\" is not in the range [-2^53, +2^53].");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testLessThanUserValueUnexpectedType()
    {
        $logLevel = Logger::DEBUG;
        $conditionList = [
          'name' => 'meters_travelled',
          'value' => 48,
          'type' => 'custom_attribute',
          'match' => 'lt'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['meters_travelled' => true],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                         ->method('log')
                         ->with($logLevel, "Audience condition {\"name\":\"meters_travelled\",\"value\":48,\"type\":\"custom_attribute\",\"match\":\"lt\"} evaluated to UNKNOWN because a value of type \"boolean\" was passed for user attribute \"meters_travelled\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testSubstringnUserValueUnexpectedType()
    {
        $logLevel = Logger::DEBUG;
        $conditionList = [
          'name' => 'headline_text',
          'value' => 'buy now',
          'type' => 'custom_attribute',
          'match' => 'substring'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['headline_text' => 1234],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                         ->method('log')
                         ->with($logLevel, "Audience condition {\"name\":\"headline_text\",\"value\":\"buy now\",\"type\":\"custom_attribute\",\"match\":\"substring\"} evaluated to UNKNOWN because a value of type \"integer\" was passed for user attribute \"headline_text\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testExactUserValueTypeMismatch()
    {
        $logLevel = Logger::DEBUG;
        $conditionList = [
          'name' => 'favorite_constellation',
          'value' => 'Lacerta',
          'type' => 'custom_attribute',
          'match' => 'exact'
        ];

        $customAttrConditionEvaluator = new CustomAttributeConditionEvaluator(
            ['favorite_constellation' => 5],
            $this->loggerMock
        );

        $this->loggerMock->expects($this->once())
                         ->method('log')
                         ->with($logLevel, "Audience condition {\"name\":\"favorite_constellation\",\"value\":\"Lacerta\",\"type\":\"custom_attribute\",\"match\":\"exact\"} evaluated to UNKNOWN because a value of type \"integer\" was passed for user attribute \"favorite_constellation\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }
}
