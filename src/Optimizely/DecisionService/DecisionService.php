<?php
/**
 * Copyright 2017-2018, Optimizely Inc and Contributors
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
namespace Optimizely\DecisionService;

use Exception;
use Monolog\Logger;
use Optimizely\Bucketer;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\FeatureFlag;
use Optimizely\Entity\Rollout;
use Optimizely\Entity\Variation;
use Optimizely\Enums\ControlAttributes;
use Optimizely\Logger\LoggerInterface;
use Optimizely\ProjectConfig;
use Optimizely\UserProfile\Decision;
use Optimizely\UserProfile\UserProfileServiceInterface;
use Optimizely\UserProfile\UserProfile;
use Optimizely\UserProfile\UserProfileUtils;
use Optimizely\Utils\Validator;

/**
 * Optimizely's decision service that determines which variation of an experiment the user will be allocated to.
 *
 * The decision service contains all logic around how a user decision is made. This includes all of the following (in order):
 *   1. Checking experiment status.
 *   2. Checking force bucketing
 *   3. Checking whitelisting.
 *   4. Check sticky bucketing.
 *   5. Checking audience targeting.
 *   6. Using Murmurhash3 to bucket the user.
 *
 * @package Optimizely
 */
class DecisionService
{
    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var ProjectConfig
     */
    private $_projectConfig;

    /**
     * @var Bucketer
     */
    private $_bucketer;

    /**
     * @var UserProfileServiceInterface
     */
    private $_userProfileService;

    /**
     * DecisionService constructor.
     *
     * @param LoggerInterface $logger
     * @param ProjectConfig   $projectConfig
     */
    public function __construct(LoggerInterface $logger, ProjectConfig $projectConfig, UserProfileServiceInterface $userProfileService = null)
    {
        $this->_logger = $logger;
        $this->_projectConfig = $projectConfig;
        $this->_bucketer = new Bucketer($logger);
        $this->_userProfileService = $userProfileService;
    }

    /**
     * Gets the ID for Bucketing
     *
     * @param string $userId         user ID
     * @param array  $userAttributes user attributes
     *
     * @return string  the bucketing ID assigned to user
     */
    private function getBucketingId($userId, $userAttributes)
    {
        // By default, the bucketing ID should be the user ID
        $bucketingId = $userId;

        // If the bucketing ID key is defined in userAttributes, then use that in
        // place of the userID for the murmur hash key
        $bucketingIdKey = ControlAttributes::BUCKETING_ID;
        
        if (!empty($userAttributes[$bucketingIdKey])) {
            $bucketingId = $userAttributes[$bucketingIdKey];
            $this->_logger->log(Logger::DEBUG, sprintf('Setting the bucketing ID to "%s".', $bucketingId));
        }

        return $bucketingId;
    }

    /**
     * Determine which variation to show the user.
     *
     * @param $experiment  Experiment Experiment to get the variation for.
     * @param $userId      string     User identifier.
     * @param $attributes  array      Attributes of the user.
     *
     * @return Variation   Variation  which the user is bucketed into.
     */
    public function getVariation(Experiment $experiment, $userId, $attributes = null)
    {
        $bucketingId = $this->getBucketingId($userId, $attributes);

        if (!$experiment->isExperimentRunning()) {
            $this->_logger->log(Logger::INFO, sprintf('Experiment "%s" is not running.', $experiment->getKey()));
            return null;
        }

        // check if a forced variation is set
        $forcedVariation = $this->_projectConfig->getForcedVariation($experiment->getKey(), $userId);
        if (!is_null($forcedVariation)) {
            return $forcedVariation;
        }

        // check if the user has been whitelisted
        $variation = $this->getWhitelistedVariation($experiment, $userId);
        if (!is_null($variation)) {
            return $variation;
        }

        // check for sticky bucketing
        $userProfile = new UserProfile($userId);
        if (!is_null($this->_userProfileService)) {
            $storedUserProfile = $this->getStoredUserProfile($userId);
            if (!is_null($storedUserProfile)) {
                $userProfile = $storedUserProfile;
                $variation = $this->getStoredVariation($experiment, $userProfile);
                if (!is_null($variation)) {
                    return $variation;
                }
            }
        }

        if (!Validator::isUserInExperiment($this->_projectConfig, $experiment, $attributes)) {
            $this->_logger->log(
                Logger::INFO,
                sprintf('User "%s" does not meet conditions to be in experiment "%s".', $userId, $experiment->getKey())
            );
            return null;
        }

        $variation = $this->_bucketer->bucket($this->_projectConfig, $experiment, $bucketingId, $userId);
        if (!is_null($variation)) {
            $this->saveVariation($experiment, $variation, $userProfile);
        }
        return $variation;
    }

    /**
     * Get the variation the user is bucketed into for the given FeatureFlag
     *
     * @param  FeatureFlag $featureFlag    The feature flag the user wants to access
     * @param  string      $userId         user ID
     * @param  array       $userAttributes user attributes
     * @return Decision  if getVariationForFeatureExperiment or getVariationForFeatureRollout returns a Decision
     *         null      otherwise
     */
    public function getVariationForFeature(FeatureFlag $featureFlag, $userId, $userAttributes)
    {
        //Evaluate in this order:
        //1. Attempt to bucket user into experiment using feature flag.
        //2. Attempt to bucket user into rollout using the feature flag.

        // Check if the feature flag is under an experiment and the the user is bucketed into one of these experiments
        $decision = $this->getVariationForFeatureExperiment($featureFlag, $userId, $userAttributes);
        if ($decision) {
            return $decision;
        }

        // Check if the feature flag has rollout and the user is bucketed into one of it's rules
        $decision = $this->getVariationForFeatureRollout($featureFlag, $userId, $userAttributes);
        if ($decision) {
            $this->_logger->log(
                Logger::INFO,
                "User '{$userId}' is bucketed into rollout for feature flag '{$featureFlag->getKey()}'."
            );
      
            return $decision;
        }
            
        $this->_logger->log(
            Logger::INFO,
            "User '{$userId}' is not bucketed into rollout for feature flag '{$featureFlag->getKey()}'."
        );

        return null;
    }

    /**
     * Get the variation if the user is bucketed for one of the experiments on this feature flag
     *
     * @param  FeatureFlag $featureFlag    The feature flag the user wants to access
     * @param  string      $userId         user id
     * @param  array       $userAttributes user userAttributes
     * @return Decision  if a variation is returned for the user
     *         null  if feature flag is not used in any experiments or no variation is returned for the user
     */
    public function getVariationForFeatureExperiment(FeatureFlag $featureFlag, $userId, $userAttributes)
    {
        $featureFlagKey = $featureFlag->getKey();
        $experimentIds = $featureFlag->getExperimentIds();

        // Check if there are any experiment IDs inside feature flag
        if (empty($experimentIds)) {
            $this->_logger->log(
                Logger::DEBUG,
                "The feature flag '{$featureFlagKey}' is not used in any experiments."
            );
            return null;
        }

        // Evaluate each experiment ID and return the first bucketed experiment variation
        foreach ($experimentIds as $experiment_id) {
            $experiment = $this->_projectConfig->getExperimentFromId($experiment_id);
            if ($experiment && !($experiment->getKey())) {
                // Error logged and exception thrown in ProjectConfig-getExperimentFromId
                continue;
            }

            $variation = $this->getVariation($experiment, $userId, $userAttributes);
            if ($variation && $variation->getKey()) {
                $this->_logger->log(
                    Logger::INFO,
                    "The user '{$userId}' is bucketed into experiment '{$experiment->getKey()}' of feature '{$featureFlagKey}'."
                );

                return new FeatureDecision($experiment, $variation, FeatureDecision::DECISION_SOURCE_EXPERIMENT);
            }
        }

        $this->_logger->log(
            Logger::INFO,
            "The user '{$userId}' is not bucketed into any of the experiments using the feature '{$featureFlagKey}'."
        );

        return null;
    }

    /**
     * Get the variation if the user is bucketed into rollout for this feature flag
     * Evaluate the user for rules in priority order by seeing if the user satisfies the audience.
     * Fall back onto the everyone else rule if the user is ever excluded from a rule due to traffic allocation.
     *
     * @param  FeatureFlag $featureFlag    The feature flag the user wants to access
     * @param  string      $userId         user id
     * @param  array       $userAttributes user userAttributes
     * @return Decision  if a variation is returned for the user
     *         null  if feature flag is not used in a rollout or
     *               no rollout found against the rollout ID or
     *               no variation is returned for the user
     */
    public function getVariationForFeatureRollout(FeatureFlag $featureFlag, $userId, $userAttributes)
    {
        $bucketing_id = $this->getBucketingId($userId, $userAttributes);
        $featureFlagKey = $featureFlag->getKey();
        $rollout_id = $featureFlag->getRolloutId();
        if (empty($rollout_id)) {
            $this->_logger->log(
                Logger::DEBUG,
                "Feature flag '{$featureFlagKey}' is not used in a rollout."
            );
            return null;
        }
        $rollout = $this->_projectConfig->getRolloutFromId($rollout_id);
        if ($rollout && !($rollout->getId())) {
            // Error logged and thrown in getRolloutFromId
            return null;
        }

        $rolloutRules = $rollout->getExperiments();
        if (sizeof($rolloutRules) == 0) {
            return null;
        }

        // Evaluate all rollout rules except for last one
        for ($i = 0; $i < sizeof($rolloutRules) - 1; $i++) {
            $experiment = $rolloutRules[$i];

            // Evaluate if user meets the audience condition of this rollout rule
            if (!Validator::isUserInExperiment($this->_projectConfig, $experiment, $userAttributes)) {
                $this->_logger->log(
                    Logger::DEBUG,
                    sprintf("User '%s' did not meet the audience conditions to be in rollout rule '%s'.", $userId, $experiment->getKey())
                );
                // Evaluate this user for the next rule
                continue;
            }

            // Evaluate if user satisfies the traffic allocation for this rollout rule
            $variation = $this->_bucketer->bucket($this->_projectConfig, $experiment, $bucketing_id, $userId);
            if ($variation && $variation->getKey()) {
                return new FeatureDecision($experiment, $variation, FeatureDecision::DECISION_SOURCE_ROLLOUT);
            }
            break;
        }
        // Evaluate Everyone Else Rule / Last Rule now
        $experiment = $rolloutRules[sizeof($rolloutRules)-1];        
        
        // Evaluate if user meets the audience condition of Everyone Else Rule / Last Rule now
        if (!Validator::isUserInExperiment($this->_projectConfig, $experiment, $userAttributes)) {
            $this->_logger->log(
                Logger::DEBUG,
                sprintf("User '%s' did not meet the audience conditions to be in rollout rule '%s'.", $userId, $experiment->getKey())
            );
            return null;
        }
        
        $variation = $this->_bucketer->bucket($this->_projectConfig, $experiment, $bucketing_id, $userId);
        if ($variation && $variation->getKey()) {
            return new FeatureDecision($experiment, $variation, FeatureDecision::DECISION_SOURCE_ROLLOUT);
        }
        return null;
    }

    /**
     * Determine variation the user has been forced into.
     *
     * @param $experiment Experiment Experiment in which user is to be bucketed.
     * @param $userId     string     string
     *
     * @return null|Variation Representing the variation the user is forced into.
     */
    private function getWhitelistedVariation(Experiment $experiment, $userId)
    {
        // Check if user is whitelisted for a variation.
        $forcedVariations = $experiment->getForcedVariations();
        if (!is_null($forcedVariations) && isset($forcedVariations[$userId])) {
            $variationKey = $forcedVariations[$userId];
            $variation = $this->_projectConfig->getVariationFromKey($experiment->getKey(), $variationKey);
            if ($variationKey) {
                $this->_logger->log(
                    Logger::INFO,
                    sprintf('User "%s" is forced in variation "%s" of experiment "%s".', $userId, $variationKey, $experiment->getKey())
                );
            }
            return $variation;
        }
        return null;
    }

    /**
     * Get the stored user profile for the given user ID.
     *
     * @param $userId string the ID of the user.
     *
     * @return null|UserProfile the stored user profile.
     */
    private function getStoredUserProfile($userId)
    {
        if (is_null($this->_userProfileService)) {
            return null;
        }

        try {
            $userProfileMap = $this->_userProfileService->lookup($userId);
            if (is_null($userProfileMap)) {
                $this->_logger->log(
                    Logger::INFO,
                    sprintf('No user profile found for user with ID "%s".', $userId)
                );
            } elseif (UserProfileUtils::isValidUserProfileMap($userProfileMap)) {
                return UserProfileUtils::convertMapToUserProfile($userProfileMap);
            } else {
                $this->_logger->log(
                    Logger::WARNING,
                    'The User Profile Service returned an invalid user profile map.'
                );
            }
        } catch (Exception $e) {
            $this->_logger->log(
                Logger::ERROR,
                sprintf('The User Profile Service lookup method failed: %s.', $e->getMessage())
            );
        }

        return null;
    }

    /**
     * Get the stored variation for the given experiment from the user profile.
     *
     * @param $experiment  Experiment  The experiment for which we are getting the stored variation.
     * @param $userProfile UserProfile The user profile from which we are getting the stored variation.
     *
     * @return null|Variation the stored variation or null if not found.
     */
    private function getStoredVariation(Experiment $experiment, UserProfile $userProfile)
    {
        $experimentKey = $experiment->getKey();
        $userId = $userProfile->getUserId();
        $variationId = $userProfile->getVariationForExperiment($experiment->getId());

        if (is_null($variationId)) {
            $this->_logger->log(
                Logger::INFO,
                sprintf('No previously activated variation of experiment "%s" for user "%s" found in user profile.', $experimentKey, $userId)
            );
            return null;
        }

        if (!$this->_projectConfig->isVariationIdValid($experimentKey, $variationId)) {
            $this->_logger->log(
                Logger::INFO,
                sprintf(
                    'User "%s" was previously bucketed into variation with ID "%s" for experiment "%s", but no matching variation was found for that user. We will re-bucket the user.',
                    $userId,
                    $variationId,
                    $experimentKey
                )
            );
            return null;
        }

        $variation = $this->_projectConfig->getVariationFromId($experimentKey, $variationId);
        $this->_logger->log(
            Logger::INFO,
            sprintf(
                'Returning previously activated variation "%s" of experiment "%s" for user "%s" from user profile.',
                $variation->getKey(),
                $experimentKey,
                $userId
            )
        );
        return $variation;
    }

    /**
     * Save the given variation assignment to the given user profile.
     *
     * @param $experiment  Experiment  Experiment for which we are storing the variation.
     * @param $variation   Variation   Variation the user is bucketed into.
     * @param $userProfile UserProfile User profile object to which we are persisting the variation assignment.
     */
    private function saveVariation(Experiment $experiment, Variation $variation, UserProfile $userProfile)
    {
        if (is_null($this->_userProfileService)) {
            return;
        }

        $experimentId = $experiment->getId();
        $decision = $userProfile->getDecisionForExperiment($experimentId);
        $variationId = $variation->getId();
        if (is_null($decision)) {
            $decision = new Decision($variationId);
        } else {
            $decision->setVariationId($variationId);
        }

        $userProfile->saveDecisionForExperiment($experimentId, $decision);
        $userProfileMap = UserProfileUtils::convertUserProfileToMap($userProfile);

        try {
            $this->_userProfileService->save($userProfileMap);
            $this->_logger->log(
                Logger::INFO,
                sprintf(
                    'Saved variation "%s" of experiment "%s" for user "%s".',
                    $variation->getKey(),
                    $experiment->getKey(),
                    $userProfile->getUserId()
                )
            );
        } catch (Exception $e) {
            $this->_logger->log(
                Logger::WARNING,
                sprintf(
                    'Failed to save variation "%s" of experiment "%s" for user "%s".',
                    $variation->getKey(),
                    $experiment->getKey(),
                    $userProfile->getUserId()
                )
            );
        }
    }
}
