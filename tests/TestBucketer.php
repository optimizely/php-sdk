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

use Optimizely\Bucketer;

/**
 * Class TestBucketer
 * Extending Bucketer for the sake of tests.
 * In PHP we cannot mock private/protected methods and so this was the most novel way to test.
 *
 * @package Optimizely\Tests
 */
class TestBucketer extends Bucketer
{
    private $values;

    public function setBucketValues($values)
    {
        $this->values = $values;
    }

    public function generateBucketValue($bucketingId)
    {
        return array_shift($this->values);
    }
}
