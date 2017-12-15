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

namespace Optimizely\UserProfile;

class UserProfileUtils
{
    const USER_ID_KEY = 'user_id';

    const EXPERIMENT_BUCKET_MAP_KEY = 'experiment_bucket_map';

    const VARIATION_ID_KEY = 'variation_id';

    /**
     * Grab the revenue value from the event tags. "revenue" is a reserved keyword.
     *
     * @param  $userProfileMap array Representing the user profile.
     * @return true if the given user profile map is valid, false otherwise.
     */
    public static function isValidUserProfileMap($userProfileMap)
    {
        if (!is_array($userProfileMap)) {
            return false;
        }

        if (!isset($userProfileMap[self::USER_ID_KEY])) {
            return false;
        }

        if (!isset($userProfileMap[self::EXPERIMENT_BUCKET_MAP_KEY])) {
            return false;
        }

        if (!is_array($userProfileMap[self::EXPERIMENT_BUCKET_MAP_KEY])) {
            return false;
        }

        // validate the experiment bucket map
        $experimentBucketMap = $userProfileMap[self::EXPERIMENT_BUCKET_MAP_KEY];
        foreach ($experimentBucketMap as $experimentId => $decision) {
            if (!is_array($decision)) {
                return false;
            }

            // make sure that there is a variation ID property in every decision
            if (!isset($decision[self::VARIATION_ID_KEY])) {
                return false;
            }
        }

        // Looks good to me
        return true;
    }

    /**
     * Convert the given user profile map into a UserProfile object.
     *
     * @param $userProfileMap array
     *
     * @return UserProfile The user profile object constructed from the given map.
     */
    public static function convertMapToUserProfile($userProfileMap)
    {
        $userId = $userProfileMap[self::USER_ID_KEY];
        $experimentBucketMap = array();
        foreach ($userProfileMap[self::EXPERIMENT_BUCKET_MAP_KEY] as $experimentId => $decisionMap) {
            $variationId = $decisionMap[self::VARIATION_ID_KEY];
            $experimentBucketMap[$experimentId] = new Decision($variationId);
        }

        return new UserProfile($userId, $experimentBucketMap);
    }

    /**
     * Convert the given UserProfile object into a map.
     *
     * @param $userProfile UserProfile The user profile object to convert to a map.
     *
     * @return array The map representing the user profile object.
     */
    public static function convertUserProfileToMap($userProfile)
    {
        $userProfileMap = array(
            self::USER_ID_KEY => $userProfile->getUserId(),
            self::EXPERIMENT_BUCKET_MAP_KEY => array()
        );
        $experimentBucketMap = $userProfile->getExperimentBucketMap();
        foreach ($experimentBucketMap as $experimentId => $decision) {
            $userProfileMap[self::EXPERIMENT_BUCKET_MAP_KEY][$experimentId] = array(
                    self::VARIATION_ID_KEY => $decision->getVariationId()
                );
        }
        return $userProfileMap;
    }
}
