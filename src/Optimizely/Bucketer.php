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
namespace Optimizely;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\Variation;

/**
 * Class Bucketer
 *
 * @package Optimizely
 */
class Bucketer
{
    /**
     * @var integer Seed to be used in bucketing hash.
     */
    private static $HASH_SEED = 1;

    /**
     * @var integer Maximum traffic allocation value.
     */
    private static $MAX_TRAFFIC_VALUE = 10000;

    /**
     * @var integer Maximum unsigned 32 bit value.
     */
    private static $UNSIGNED_MAX_32_BIT_VALUE = 0xFFFFFFFF;

    /**
     * @var integer Maximum possible hash value.
     */
    private static $MAX_HASH_VALUE = 0x100000000;

    /**
     * Generate a hash value to be used in determining which variation the user will be put in.
     *
     * @param $bucketingId string ID to be used to bucket the user in the experiment.
     *
     * @return integer Unsigned value denoting the hash value for the user.
     */
    private function generateHashCode($bucketingId)
    {
        return murmurhash3_int($bucketingId, Bucketer::$HASH_SEED) & Bucketer::$UNSIGNED_MAX_32_BIT_VALUE;
    }

    /**
     * Generate an integer to be used in bucketing user to a particular variation.
     *
     * @param $bucketingId string ID to be used to bucket the user in the experiment.
     *
     * @return integer Value in the closed range [0, 9999] denoting the bucket the user belongs to.
     */
    protected function generateBucketValue($bucketingId)
    {
        $hashCode = $this->generateHashCode($bucketingId);
        $ratio = $hashCode / Bucketer::$MAX_HASH_VALUE;
        return floor($ratio * Bucketer::$MAX_TRAFFIC_VALUE);
    }

    /**
     * @param $userId string ID for user.
     * @param $parentId mixed ID representing Experiment or Group.
     * @param $trafficAllocations array Traffic allocations for variation or experiment.
     *
     * @returns string ID representing experiment or variation.
     */
    private function findBucket($userId, $parentId, $trafficAllocations)
    {
        // Generate bucketing ID based on combination of user ID and experiment ID or group ID.
        $bucketingId = $userId.$parentId;

        $bucketingNumber = $this->generateBucketValue($bucketingId);

        forEach ($trafficAllocations as $trafficAllocation)
        {
            $currentEnd = $trafficAllocation->getEndOfRange();
            if ($bucketingNumber < $currentEnd) {
                return $trafficAllocation->getEntityId();
            }
        }

        return null;
    }

    /**
     * Determine variation the user should be put in.
     *
     * @param $config ProjectConfig Configuration for the project.
     * @param $experiment Experiment Experiment in which user is to be bucketed.
     * @param $userId string User identifier.
     *
     * @return Variation Variation which will be shown to the user.
     */
    public function bucket(ProjectConfig $config, Experiment $experiment, $userId)
    {
        if (is_null($experiment->getKey())) {
            return new Variation();
        }

        // Check if user is whitelisted for a variation.
        $forcedVariations = $experiment->getForcedVariations();
        if (!is_null($forcedVariations) && isset($forcedVariations[$userId])) {
            $variationKey = $forcedVariations[$userId];
            $variation = $config->getVariationFromKey($experiment->getKey(), $variationKey);
            return $variation;
        }

        // Determine if experiment is in a mutually exclusive group.
        if ($experiment->isInMutexGroup()) {
            $group = $config->getGroup($experiment->getGroupId());

            if (is_null($group->getId())) {
                return new Variation();
            }

            $userExperimentId = $this->findBucket($userId, $group->getId(), $group->getTrafficAllocation());
            if (is_null($userExperimentId)) {
                return new Variation();
            }

            if ($userExperimentId != $experiment->getId()) {
                return new Variation();
            }
        }

        // Bucket user if not in whitelist and in group (if any).
        $variationId = $this->findBucket($userId, $experiment->getId(), $experiment->getTrafficAllocation());
        if (!is_null($variationId)) {
            $variation = $config->getVariationFromId($experiment->getKey(), $variationId);
            return $variation;
        }

        return new Variation();
    }
}
