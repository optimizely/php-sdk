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

class ConditionDecoderTest extends \PHPUnit_Framework_TestCase
{
    private $conditionDecoder;

    public function setUp()
    {
        $this->conditionDecoder = new ConditionDecoder();
        $conditions = "[\"and\", [\"or\", [\"or\", {\"name\": \"device_type\", \"type\": \"custom_attribute\", \"value\": \"iPhone\"}]], [\"or\", [\"or\", {\"name\": \"location\", \"type\": \"custom_attribute\", \"value\": \"San Francisco\"}]]]";
        $this->conditionDecoder->deserializeAudienceConditions($conditions);
    }

    public function testGetConditionsList()
    {
        $this->assertEquals(
            [
            'and', [
                'or', [
                    'or', (object)[
                        'name' => 'device_type',
                        'type' => 'custom_attribute',
                        'value' => 'iPhone'
                    ]
                ]
            ], [
                'or', [
                    'or', (object)[
                        'name' => 'location',
                        'type' => 'custom_attribute',
                        'value' => 'San Francisco'
                    ]
                ]
            ]
            ],
            $this->conditionDecoder->getConditionsList()
        );
    }
}
