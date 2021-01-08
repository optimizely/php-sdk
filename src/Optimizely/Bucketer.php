<?php
/**
 * Copyright 2016-2021, Optimizely
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
use Optimizely\Config\ProjectConfigInterface;
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
        $bucketVal = intval(floor($ratio * Bucketer::$MAX_TRAFFIC_VALUE));

        /* murmurhash3_int returns both positive and negative integers for PHP x86 versions
        it returns negative integers when it tries to create 2^32 integers while PHP doesn't support
        unsigned integers and can store integers only upto 2^31.
        Observing generated hashcodes and their corresponding bucket values after normalization
        indicates that a negative bucket number on x86 is exactly 10,000 less than it's
        corresponding bucket number on x64. Hence we can safely add 10,000 to a negative number to
        make it consistent across both of the PHP variants. */
        
        if ($bucketVal < 0) {
            $bucketVal += 10000;
        }

        return $bucketVal;
    }

    /**
     * @param $bucketingId string A customer-assigned value used to create the key for the murmur hash.
     * @param $userId string ID for user.
     * @param $parentId mixed ID representing Experiment or Group.
     * @param $trafficAllocations array Traffic allocations for variation or experiment.
     *
     * @return [ string, array ]  ID representing experiment or variation and array of log messages representing decision making.
     */
    private function findBucket($bucketingId, $userId, $parentId, $trafficAllocations)
    {
        $decideReasons = [];
        // Generate the bucketing key based on combination of user ID and experiment ID or group ID.
        $bucketingKey = $bucketingId.$parentId;
        $bucketingNumber = $this->generateBucketValue($bucketingKey);
        $message = sprintf('Assigned bucket %s to user "%s" with bucketing ID "%s".', $bucketingNumber, $userId, $bucketingId);
        $this->_logger->log(Logger::DEBUG, $message);
        $decideReasons[] = $message;

        foreach ($trafficAllocations as $trafficAllocation) {
            $currentEnd = $trafficAllocation->getEndOfRange();
            if ($bucketingNumber < $currentEnd) {
                return [$trafficAllocation->getEntityId(), $decideReasons];
            }
        }

        return [null, $decideReasons];
    }

    /**
     * Determine variation the user should be put in.
     *
     * @param $config ProjectConfigInterface Configuration for the project.
     * @param $experiment Experiment Experiment or Rollout rule in which user is to be bucketed.
     * @param $bucketingId string A customer-assigned value used to create the key for the murmur hash.
     * @param $userId string User identifier.
     *
     * @return [ Variation, array ]  Variation which will be shown to the user and array of log messages representing decision making.
     */
    public function bucket(ProjectConfigInterface $config, Experiment $experiment, $bucketingId, $userId)
    {
        $decideReasons = [];

        if (is_null($experiment->getKey())) {
            return [ null, $decideReasons ];
        }

        // Determine if experiment is in a mutually exclusive group.
        // This will not affect evaluation of rollout rules.
        if ($experiment->isInMutexGroup()) {
            $group = $config->getGroup($experiment->getGroupId());

            if (is_null($group->getId())) {
                return [ null, $decideReasons ];
            }

            list($userExperimentId, $reasons) = $this->findBucket($bucketingId, $userId, $group->getId(), $group->getTrafficAllocation());
            $decideReasons = array_merge($decideReasons, $reasons);

            if (empty($userExperimentId)) {
                $message = sprintf('User "%s" is in no experiment.', $userId);
                $this->_logger->log(Logger::INFO, $message);
                $decideReasons[] = $message;
                return [ null, $decideReasons ];
            }

            if ($userExperimentId != $experiment->getId()) {
                $message = sprintf(
                    'User "%s" is not in experiment %s of group %s.',
                    $userId,
                    $experiment->getKey(),
                    $experiment->getGroupId()
                );

                $this->_logger->log(Logger::INFO, $message);
                $decideReasons[] = $message;
                return [ null, $decideReasons ];
            }

            $message = sprintf(
                'User "%s" is in experiment %s of group %s.',
                $userId,
                $experiment->getKey(),
                $experiment->getGroupId()
            );

            $this->_logger->log(Logger::INFO, $message);
            $decideReasons[] = $message;
        }

        // Bucket user if not in whitelist and in group (if any).
        list($variationId, $reasons) = $this->findBucket($bucketingId, $userId, $experiment->getId(), $experiment->getTrafficAllocation());
        $decideReasons = array_merge($decideReasons, $reasons);
        if (!empty($variationId)) {
            $variation = $config->getVariationFromId($experiment->getKey(), $variationId);

            return [ $variation, $decideReasons ];
        }
        
        return [ null, $decideReasons ];
    }
}
