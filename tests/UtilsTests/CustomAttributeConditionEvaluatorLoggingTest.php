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
                        ->with($logLevel,
                            "Audience condition \"{\"name\":\"favorite_constellation\",\"value\":\"Lacerta\",\"type\":\"custom_attribute\",\"match\":\"regex\"}\" uses an unknown match type."
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
                        ->with($logLevel,
                            "Audience condition \"{\"name\":\"favorite_constellation\",\"value\":\"Lacerta\",\"type\":\"sdk_version\",\"match\":\"exact\"}\" has an unknown condition type."
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
                         ->with($logLevel, "Audience condition {\"name\":\"favorite_constellation\",\"value\":\"Lacerta\",\"type\":\"custom_attribute\",\"match\":\"exact\"} evaluated as UNKNOWN because no value was passed for user attribute \"favorite_constellation\".");

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
                         ->with($logLevel, "Audience condition {\"name\":\"meters_travelled\",\"value\":48,\"type\":\"custom_attribute\",\"match\":\"gt\"} evaluated as UNKNOWN because no value was passed for user attribute \"meters_travelled\".");

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
                         ->with($logLevel, "Audience condition {\"name\":\"meters_travelled\",\"value\":48,\"type\":\"custom_attribute\",\"match\":\"lt\"} evaluated as UNKNOWN because no value was passed for user attribute \"meters_travelled\".");

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
                         ->with($logLevel, "Audience condition {\"name\":\"headline_text\",\"value\":\"buy now\",\"type\":\"custom_attribute\",\"match\":\"substring\"} evaluated as UNKNOWN because no value was passed for user attribute \"headline_text\".");

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

    public function testExactUserValueUnexpectedType()
    {
        $logLevel = Logger::WARNING;
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
                         ->with($logLevel, "Audience condition {\"name\":\"favorite_constellation\",\"value\":\"Lacerta\",\"type\":\"custom_attribute\",\"match\":\"exact\"} evaluated as UNKNOWN because the value for user attribute \"favorite_constellation\" is inapplicable: \"[]\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testGreaterThanUserValueUnexpectedType()
    {
        $logLevel = Logger::WARNING;
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
                         ->with($logLevel, "Audience condition {\"name\":\"meters_travelled\",\"value\":48,\"type\":\"custom_attribute\",\"match\":\"gt\"} evaluated as UNKNOWN because the value for user attribute \"meters_travelled\" is inapplicable: \"\"48\"\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testLessThanUserValueUnexpectedType()
    {
        $logLevel = Logger::WARNING;
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
                         ->with($logLevel, "Audience condition {\"name\":\"meters_travelled\",\"value\":48,\"type\":\"custom_attribute\",\"match\":\"lt\"} evaluated as UNKNOWN because the value for user attribute \"meters_travelled\" is inapplicable: \"true\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }

    public function testSubstringnUserValueUnexpectedType()
    {
        $logLevel = Logger::WARNING;
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
                         ->with($logLevel, "Audience condition {\"name\":\"headline_text\",\"value\":\"buy now\",\"type\":\"custom_attribute\",\"match\":\"substring\"} evaluated as UNKNOWN because the value for user attribute \"headline_text\" is inapplicable: \"1234\".");

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
                         ->with($logLevel, "Audience condition {\"name\":\"favorite_constellation\",\"value\":\"Lacerta\",\"type\":\"custom_attribute\",\"match\":\"exact\"} evaluated as UNKNOWN because the value for user attribute \"favorite_constellation\" is \"integer\" while expected is \"string\".");

        $customAttrConditionEvaluator->evaluate($conditionList);
    }
}
