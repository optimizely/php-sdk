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

use Optimizely\Entity\Experiment;
use Optimizely\UserProfile\Decision;
use Optimizely\UserProfile\UserProfile;
use Optimizely\UserProfile\UserProfileUtils;

class UserProfileUtilsTest extends \PHPUnit_Framework_TestCase
{
    private $userProfileMap;

    protected function setUp()
    {
        $this->userProfileMap = array(
            'user_id' => 'test_user',
            'experiment_bucket_map' => array(
                '111111' => array(
                    'variation_id' => '211111'
                ),
                '111112' => array(
                    'variation_id' => '211113'
                )
            )
        );
    }

    public function testIsValidUserProfileMap()
    {
        $profileNotArray = 'string';
        $this->assertFalse(UserProfileUtils::isValidUserProfileMap($profileNotArray));

        $profileWithMissingId = array(
            'experiment_bucket_map' => array()
        );
        $this->assertFalse(UserProfileUtils::isValidUserProfileMap($profileWithMissingId));

        $profileWithMissingExperimentBucketMap = array(
            'user_id' => null,
        );
        $this->assertFalse(UserProfileUtils::isValidUserProfileMap($profileWithMissingExperimentBucketMap));

        $profileWithBadExperimentBucketMap = array(
            'user_id' => null,
            'experiment_bucket_map' => array(
                '111111' => array(
                    'not_expected_key' => '211111'
                )
            )
        );
        $this->assertFalse(UserProfileUtils::isValidUserProfileMap($profileWithBadExperimentBucketMap));

        $validUserProfileMap = $this->userProfileMap;
        $this->assertTrue(UserProfileUtils::isValidUserProfileMap($validUserProfileMap));
    }

    public function testConvertMapToUserProfile()
    {
        $expectedUserProfile = new UserProfile(
            'test_user',
            array(
                '111111' => new Decision('211111'),
                '111112' => new Decision('211113')
            )
        );

        $this->assertEquals($expectedUserProfile, UserProfileUtils::convertMapToUserProfile($this->userProfileMap));
    }

    public function testConvertUserProfileToMap()
    {
        $userProfileObject = new UserProfile(
            'test_user',
            array(
                '111111' => new Decision('211111'),
                '111112' => new Decision('211113')
            )
        );

        $expectedUserProfileMap = array(
            'user_id' => 'test_user',
            'experiment_bucket_map' => array(
                '111111' => array('variation_id' => '211111'),
                '111112' => array('variation_id' => '211113')
            )
        );

        $this->assertEquals($expectedUserProfileMap, UserProfileUtils::convertUserProfileToMap($userProfileObject));
    }
}
