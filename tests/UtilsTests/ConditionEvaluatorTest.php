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

    public function testEvaluateEmptyUserAttributes()
    {
        $userAttributes = [];
        $this->assertFalse($this->conditionEvaluator->evaluate($this->conditionsList, $userAttributes));
    }

    public function testEvaluateNullUserAttributes()
    {
        $userAttributes = null;
        $this->assertFalse($this->conditionEvaluator->evaluate($this->conditionsList, $userAttributes));
    }
}
