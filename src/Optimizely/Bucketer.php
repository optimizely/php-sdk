<?php
/**
 * Copyright 2016-2018, Optimizely
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

use Monolog\Logger;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\Variation;
use Optimizely\Logger\LoggerInterface;

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
     * @var LoggerInterface Logger for logging messages.
     */
    private $_logger;

    /**
     * Bucketer constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Generate a hash value to be used in determining which variation the user will be put in.
     *
     * @param $bucketingKey string Value used for the key of the murmur hash.
     *
     * @return integer Unsigned value denoting the hash value for the user.
     */
    private function generateHashCode($bucketingKey)
    {
        return murmurhash3_int($bucketingKey, Bucketer::$HASH_SEED) & Bucketer::$UNSIGNED_MAX_32_BIT_VALUE;
    }

    /**
     * Generate an integer to be used in bucketing user to a particular variation.
     *
     * @param $bucketingKey string Value used for the key of the murmur hash.
     *
     * @return integer Value in the closed range [0, 9999] denoting the bucket the user belongs to.
     */
    protected function generateBucketValue($bucketingKey)
    {
        $hashCode = $this->generateHashCode($bucketingKey);
        $ratio = $hashCode / Bucketer::$MAX_HASH_VALUE;
        return intval(floor($ratio * Bucketer::$MAX_TRAFFIC_VALUE));
    }

    /**
     * @param $bucketingId string A customer-assigned value used to create the key for the murmur hash.
     * @param $userId string ID for user.
     * @param $parentId mixed ID representing Experiment or Group.
     * @param $trafficAllocations array Traffic allocations for variation or experiment.
     *
     * @return string ID representing experiment or variation.
     */
    private function findBucket($bucketingId, $userId, $parentId, $trafficAllocations)
    {
        // Generate the bucketing key based on combination of user ID and experiment ID or group ID.
        $bucketingKey = $bucketingId.$parentId;
        $bucketingNumber = $this->generateBucketValue($bucketingKey);
        $this->_logger->log(Logger::DEBUG, sprintf('Assigned bucket %s to user "%s" with bucketing ID "%s".', $bucketingNumber, $userId, $bucketingId));

        foreach ($trafficAllocations as $trafficAllocation) {
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
     * @param $bucketingId string A customer-assigned value used to create the key for the murmur hash.
     * @param $userId string User identifier.
     *
     * @return Variation Variation which will be shown to the user.
     */
    public function bucket(ProjectConfig $config, Experiment $experiment, $bucketingId, $userId)
    {
        if (is_null($experiment->getKey())) {
            return null;
        }

        // Determine if experiment is in a mutually exclusive group.
        if ($experiment->isInMutexGroup()) {
            $group = $config->getGroup($experiment->getGroupId());

            if (is_null($group->getId())) {
                return null;
            }

            $userExperimentId = $this->findBucket($bucketingId, $userId, $group->getId(), $group->getTrafficAllocation());
            if (empty($userExperimentId)) {
                $this->_logger->log(Logger::INFO, sprintf('User "%s" is in no experiment.', $userId));
                return null;
            }

            if ($userExperimentId != $experiment->getId()) {
                $this->_logger->log(
                    Logger::INFO,
                    sprintf(
                        'User "%s" is not in experiment %s of group %s.',
                        $userId,
                        $experiment->getKey(),
                        $experiment->getGroupId()
                    )
                );
                return null;
            }

            $this->_logger->log(
                Logger::INFO,
                sprintf(
                    'User "%s" is in experiment %s of group %s.',
                    $userId,
                    $experiment->getKey(),
                    $experiment->getGroupId()
                )
            );
        }

        // Bucket user if not in whitelist and in group (if any).
        $variationId = $this->findBucket($bucketingId, $userId, $experiment->getId(), $experiment->getTrafficAllocation());
        if (!empty($variationId)) {
            $variation = $config->getVariationFromId($experiment->getKey(), $variationId);

            $this->_logger->log(
                Logger::INFO,
                sprintf(
                    'User "%s" is in variation %s of experiment %s.',
                    $userId,
                    $variation->getKey(),
                    $experiment->getKey()
                )
            );
            return $variation;
        }
        
        $this->_logger->log(Logger::INFO, sprintf('User "%s" is in no variation.', $userId));
        return null;
    }
}
