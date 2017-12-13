<?php
/**
 * Copyright 2017, Optimizely
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

use Optimizely\Entity\Variation;
use Optimizely\ProjectConfig;

class ExperimentTest extends \PHPUnit_Framework_TestCase
{
    public function testGetVariationFromId()
    {
        $this->config = new ProjectConfig(DATAFILE, null, null);
        $experiment = $this->config->getExperimentFromKey('test_experiment');

        // assert getVariationFromId returns expected variation object given valid ID
        $expected_variation = new Variation('7722370027', 'control');
        $this->assertEquals(
            $expected_variation,
            $experiment->getVariationFromId('7722370027')
        );

        // assert getVariationFromId returns null given invalid variation ID
        $this->assertNull($experiment->getVariationFromId('abracadabra'));
    }
}
