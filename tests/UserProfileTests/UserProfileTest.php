<?php
/**
 * Copyright 2017, Optimizely Inc and Contributors
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

use Optimizely\UserProfile\Decision;
use Optimizely\UserProfile\UserProfile;

class UserProfileTest extends \PHPUnit_Framework_TestCase
{
    private $userProfile;

    protected function setUp()
    {
        $experimentBucketMap = array();
        $experimentBucketMap['111111'] = new Decision('211111');
        $this->userProfile = new UserProfile(
            'user_1',
            $experimentBucketMap
        );
    }

    public function testGetVariationForExperiment()
    {
        $this->assertEquals('211111', $this->userProfile->getVariationForExperiment('111111'));
    }

    public function testGetDecisionForExperiment()
    {
        $expectedDecision = new Decision('211111');
        $this->assertEquals($expectedDecision, $this->userProfile->getDecisionForExperiment('111111'));
    }
}
