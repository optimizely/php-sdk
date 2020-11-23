<?php
/**
 * Copyright 2017-2020, Optimizely Inc and Contributors
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
use Optimizely\Config\ProjectConfigInterface;
use Optimizely\Decide\OptimizelyDecideOption;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\FeatureFlag;
use Optimizely\Entity\Rollout;
use Optimizely\Entity\Variation;
use Optimizely\Enums\ControlAttributes;
use Optimizely\Logger\LoggerInterface;
use Optimizely\Optimizely;
use Optimizely\UserProfile\Decision;
use Optimizely\UserProfile\UserProfileServiceInterface;
use Optimizely\UserProfile\UserProfile;
use Optimizely\UserProfile\UserProfileUtils;
use Optimizely\Utils\Errors;
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
     * @var Bucketer
     */
    private $_bucketer;

    /**
     * @var UserProfileServiceInterface
     */
    private $_userProfileService;


    /**
     * @var array Associative array of user IDs to an associative array
     * of experiments to variations. This contains all the forced variations
     * set by the user by calling setForcedVariation (it is not the same as the
     * whitelisting forcedVariations data structure in the Experiments class).
     */
    private $_forcedVariationMap;

    /**
     * DecisionService constructor.
     *
     * @param LoggerInterface       $logger
     * @param UserProfileServiceInterface  $userProfileService
     */
    public function __construct(LoggerInterface $logger, UserProfileServiceInterface $userProfileService = null)
    {
        $this->_logger = $logger;
        $this->_bucketer = new Bucketer($logger);
        $this->_userProfileService = $userProfileService;
        $this->_forcedVariationMap = [];
    }

    /**
     * Gets the ID for Bucketing
     *
     * @param string $userId         user ID
     * @param array  $userAttributes user attributes
     *
     * @return String representing bucketing ID if it is a String type in attributes else return user ID.
     */
    protected function getBucketingId($userId, $userAttributes, &$decideReasons = [])
    {
        $bucketingIdKey = ControlAttributes::BUCKETING_ID;

        if (isset($userAttributes[$bucketingIdKey])) {
            if (is_string($userAttributes[$bucketingIdKey])) {
                return $userAttributes[$bucketingIdKey];
            }

            $message = 'Bucketing ID attribute is not a string. Defaulted to user ID.';
            $this->_logger->log(Logger::WARNING, $message);
            $decideReasons[] = $message;
        }
        return $userId;
    }

    /**
     * Determine which variation to show the user.
     *
     * @param $projectConfig    ProjectConfigInterface   ProjectConfigInterface instance.
     * @param $experiment       Experiment      Experiment to get the variation for.
     * @param $userId           string          User identifier.
     * @param $attributes       array           Attributes of the user.
     *
     * @return Variation   Variation  which the user is bucketed into.
     */
    public function getVariation(ProjectConfigInterface $projectConfig, Experiment $experiment, $userId, $attributes = null, $decideOptions = [], &$decideReasons = [])
    {
        $bucketingId = $this->getBucketingId($userId, $attributes, $decideReasons);

        if (!$experiment->isExperimentRunning()) {
            $message = sprintf('Experiment "%s" is not running.', $experiment->getKey());
            $this->_logger->log(Logger::INFO, $message);
            $decideReasons[] = $message;
            return null;
        }

        // check if a forced variation is set
        $forcedVariation = $this->getForcedVariation($projectConfig, $experiment->getKey(), $userId, $decideReasons);
        if (!is_null($forcedVariation)) {
            return $forcedVariation;
        }

        // check if the user has been whitelisted
        $variation = $this->getWhitelistedVariation($projectConfig, $experiment, $userId, $decideReasons);
        if (!is_null($variation)) {
            return $variation;
        }

        // check for sticky bucketing
        if (!in_array(OptimizelyDecideOption::IGNORE_USER_PROFILE_SERVICE, $decideOptions)) {
            $userProfile = new UserProfile($userId);
            if (!is_null($this->_userProfileService)) {
                $storedUserProfile = $this->getStoredUserProfile($userId, $decideReasons);
                if (!is_null($storedUserProfile)) {
                    $userProfile = $storedUserProfile;
                    $variation = $this->getStoredVariation($projectConfig, $experiment, $userProfile, $decideReasons);
                    if (!is_null($variation)) {
                        return $variation;
                    }
                }
            }
        }

        if (!Validator::doesUserMeetAudienceConditions($projectConfig, $experiment, $attributes, $this->_logger)) {
            $message = sprintf('User "%s" does not meet conditions to be in experiment "%s".', $userId, $experiment->getKey());
            $this->_logger->log(
                Logger::INFO,
                $message
            );
            $decideReasons[] = $message;
            return null;
        }

        $variation = $this->_bucketer->bucket($projectConfig, $experiment, $bucketingId, $userId, $decideReasons);
        if ($variation === null) {
            $message = sprintf('User "%s" is in no variation.', $userId);
            $this->_logger->log(Logger::INFO, $message);
            $decideReasons[] = $message;
        } else {
            if (!in_array(OptimizelyDecideOption::IGNORE_USER_PROFILE_SERVICE, $decideOptions)) {
                $this->saveVariation($experiment, $variation, $userProfile, $decideReasons);
            }
            $message = sprintf(
                'User "%s" is in variation %s of experiment %s.',
                $userId,
                $variation->getKey(),
                $experiment->getKey()
            );
            $this->_logger->log(
                Logger::INFO,
                $message
            );
            $decideReasons[] = $message;
        }

        return $variation;
    }

    /**
     * Get the variation the user is bucketed into for the given FeatureFlag
     *
     * @param  ProjectConfigInterface $projectConfig  ProjectConfigInterface instance.
     * @param  FeatureFlag   $featureFlag    The feature flag the user wants to access
     * @param  string        $userId         user ID
     * @param  array         $userAttributes user attributes
     * @return Decision  if getVariationForFeatureExperiment or getVariationForFeatureRollout returns a Decision
     *         null      otherwise
     */
    public function getVariationForFeature(ProjectConfigInterface $projectConfig, FeatureFlag $featureFlag, $userId, $userAttributes, $decideOptions = [], &$decideReasons = [])
    {
        //Evaluate in this order:
        //1. Attempt to bucket user into experiment using feature flag.
        //2. Attempt to bucket user into rollout using the feature flag.

        // Check if the feature flag is under an experiment and the the user is bucketed into one of these experiments
        $decision = $this->getVariationForFeatureExperiment($projectConfig, $featureFlag, $userId, $userAttributes, $decideOptions, $decideReasons);
        if ($decision) {
            return $decision;
        }

        // Check if the feature flag has rollout and the user is bucketed into one of it's rules
        $decision = $this->getVariationForFeatureRollout($projectConfig, $featureFlag, $userId, $userAttributes, $decideReasons);
        if ($decision) {
            $message = "User '{$userId}' is bucketed into rollout for feature flag '{$featureFlag->getKey()}'.";
            $this->_logger->log(
                Logger::INFO,
                $message
            );
            $decideReasons[] = $message;

            return $decision;
        }

        $message = "User '{$userId}' is not bucketed into rollout for feature flag '{$featureFlag->getKey()}'.";
        $this->_logger->log(
            Logger::INFO,
            $message
        );
        $decideReasons[] = $message;

        return new FeatureDecision(null, null, FeatureDecision::DECISION_SOURCE_ROLLOUT);
    }

    /**
     * Get the variation if the user is bucketed for one of the experiments on this feature flag
     *
     * @param  ProjectConfigInterface $projectConfig  ProjectConfigInterface instance.
     * @param  FeatureFlag   $featureFlag    The feature flag the user wants to access
     * @param  string        $userId         user id
     * @param  array         $userAttributes user userAttributes
     * @return Decision  if a variation is returned for the user
     *         null  if feature flag is not used in any experiments or no variation is returned for the user
     */
    public function getVariationForFeatureExperiment(ProjectConfigInterface $projectConfig, FeatureFlag $featureFlag, $userId, $userAttributes, $decideOptions = [], &$decideReasons = [])
    {
        $featureFlagKey = $featureFlag->getKey();
        $experimentIds = $featureFlag->getExperimentIds();

        // Check if there are any experiment IDs inside feature flag
        if (empty($experimentIds)) {
            $message = "The feature flag '{$featureFlagKey}' is not used in any experiments.";
            $this->_logger->log(
                Logger::DEBUG,
                $message
            );
            $decideReasons[] = $message;
            return null;
        }

        // Evaluate each experiment ID and return the first bucketed experiment variation
        foreach ($experimentIds as $experiment_id) {
            $experiment = $projectConfig->getExperimentFromId($experiment_id);
            if ($experiment && !($experiment->getKey())) {
                // Error logged and exception thrown in ProjectConfigInterface-getExperimentFromId
                continue;
            }

            $variation = $this->getVariation($projectConfig, $experiment, $userId, $userAttributes, $decideOptions, $decideReasons);
            if ($variation && $variation->getKey()) {
                $message = "The user '{$userId}' is bucketed into experiment '{$experiment->getKey()}' of feature '{$featureFlagKey}'.";
                $this->_logger->log(
                    Logger::INFO,
                    $message
                );
                $decideReasons[] = $message;

                return new FeatureDecision($experiment, $variation, FeatureDecision::DECISION_SOURCE_FEATURE_TEST);
            }
        }

        $message = "The user '{$userId}' is not bucketed into any of the experiments using the feature '{$featureFlagKey}'.";
        $this->_logger->log(
            Logger::INFO,
            $message
        );
        $decideReasons[] = $message;

        return null;
    }

    /**
     * Get the variation if the user is bucketed into rollout for this feature flag
     * Evaluate the user for rules in priority order by seeing if the user satisfies the audience.
     * Fall back onto the everyone else rule if the user is ever excluded from a rule due to traffic allocation.
     *
     * @param  ProjectConfigInterface $projectConfig  ProjectConfigInterface instance.
     * @param  FeatureFlag   $featureFlag    The feature flag the user wants to access
     * @param  string        $userId         user id
     * @param  array         $userAttributes user userAttributes
     * @return Decision  if a variation is returned for the user
     *         null  if feature flag is not used in a rollout or
     *               no rollout found against the rollout ID or
     *               no variation is returned for the user
     */
    public function getVariationForFeatureRollout(ProjectConfigInterface $projectConfig, FeatureFlag $featureFlag, $userId, $userAttributes, &$decideReasons = [])
    {
        $bucketing_id = $this->getBucketingId($userId, $userAttributes, $decideReasons);
        $featureFlagKey = $featureFlag->getKey();
        $rollout_id = $featureFlag->getRolloutId();
        if (empty($rollout_id)) {
            $message = "Feature flag '{$featureFlagKey}' is not used in a rollout.";
            $this->_logger->log(
                Logger::DEBUG,
                $message
            );
            $decideReasons[] = $message;
            return null;
        }
        $rollout = $projectConfig->getRolloutFromId($rollout_id);
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
            $rolloutRule = $rolloutRules[$i];

            // Evaluate if user meets the audience condition of this rollout rule
            if (!Validator::doesUserMeetAudienceConditions($projectConfig, $rolloutRule, $userAttributes, $this->_logger, 'Optimizely\Enums\RolloutAudienceEvaluationLogs', $i + 1)) {
                $message = sprintf("User '%s' does not meet conditions for targeting rule %s.", $userId, $i+1);
                $this->_logger->log(
                    Logger::DEBUG,
                    $message
                );
                $decideReasons[] = $message;
                // Evaluate this user for the next rule
                continue;
            }

            // Evaluate if user satisfies the traffic allocation for this rollout rule
            $variation = $this->_bucketer->bucket($projectConfig, $rolloutRule, $bucketing_id, $userId, $decideReasons);
            if ($variation && $variation->getKey()) {
                return new FeatureDecision($rolloutRule, $variation, FeatureDecision::DECISION_SOURCE_ROLLOUT);
            }
            break;
        }
        // Evaluate Everyone Else Rule / Last Rule now
        $rolloutRule = $rolloutRules[sizeof($rolloutRules) - 1];

        // Evaluate if user meets the audience condition of Everyone Else Rule / Last Rule now
        if (!Validator::doesUserMeetAudienceConditions($projectConfig, $rolloutRule, $userAttributes, $this->_logger, 'Optimizely\Enums\RolloutAudienceEvaluationLogs', 'Everyone Else')) {
            $message = sprintf("User '%s' does not meet conditions for targeting rule 'Everyone Else'.", $userId);
            $this->_logger->log(
                Logger::DEBUG,
                $message
            );
            $decideReasons[] = $message;
            return null;
        }

        $variation = $this->_bucketer->bucket($projectConfig, $rolloutRule, $bucketing_id, $userId, $decideReasons);
        if ($variation && $variation->getKey()) {
            return new FeatureDecision($rolloutRule, $variation, FeatureDecision::DECISION_SOURCE_ROLLOUT);
        }
        return null;
    }


    /**
     * Gets the forced variation key for the given user and experiment.
     *
     * @param $projectConfig ProjectConfigInterface  ProjectConfigInterface instance.
     * @param $experimentKey string         Key for experiment.
     * @param $userId        string         The user Id.
     *
     * @return Variation The variation which the given user and experiment should be forced into.
     */
    public function getForcedVariation(ProjectConfigInterface $projectConfig, $experimentKey, $userId, &$decideReasons = [])
    {
        if (!isset($this->_forcedVariationMap[$userId])) {
            $this->_logger->log(Logger::DEBUG, sprintf('User "%s" is not in the forced variation map.', $userId));
            return null;
        }

        $experimentToVariationMap = $this->_forcedVariationMap[$userId];
        $experimentId = $projectConfig->getExperimentFromKey($experimentKey)->getId();

        // check for null and empty string experiment ID
        if (strlen($experimentId) == 0) {
            // this case is logged in getExperimentFromKey
            return null;
        }

        if (!isset($experimentToVariationMap[$experimentId])) {
            $message = sprintf('No experiment "%s" mapped to user "%s" in the forced variation map.', $experimentKey, $userId);
            $this->_logger->log(Logger::DEBUG, $message);
            $decideReasons[] = $message;
            return null;
        }

        $variationId = $experimentToVariationMap[$experimentId];
        $variation = $projectConfig->getVariationFromId($experimentKey, $variationId);
        $variationKey = $variation->getKey();

        $message = sprintf('Variation "%s" is mapped to experiment "%s" and user "%s" in the forced variation map', $variationKey, $experimentKey, $userId);
        $this->_logger->log(Logger::DEBUG, $message);
        $decideReasons[] = $message;
        return $variation;
    }

    /**
     * Sets an associative array of user IDs to an associative array of experiments
     * to forced variations.
     *
     * @param $projectConfig ProjectConfigInterface  ProjectConfigInterface instance.
     * @param $experimentKey string         Key for experiment.
     * @param $userId        string         The user Id.
     * @param $variationKey  string         Key for variation. If null, then clear the existing experiment-to-variation mapping.
     *
     * @return boolean A boolean value that indicates if the set completed successfully.
     */
    public function setForcedVariation(ProjectConfigInterface $projectConfig, $experimentKey, $userId, $variationKey)
    {
        // check for empty string Variation key
        if (!is_null($variationKey) && !Validator::validateNonEmptyString($variationKey)) {
            $this->_logger->log(Logger::ERROR, sprintf(Errors::INVALID_FORMAT, Optimizely::VARIATION_KEY));
            return false;
        }

        $experiment = $projectConfig->getExperimentFromKey($experimentKey);
        $experimentId = $experiment->getId();

        // check if the experiment exists in the datafile (a new experiment is returned if it is not in the datafile)
        if (strlen($experimentId) == 0) {
            // this case is logged in getExperimentFromKey
            return false;
        }

        // clear the forced variation if the variation key is null
        if (is_null($variationKey)) {
            unset($this->_forcedVariationMap[$userId][$experimentId]);
            $this->_logger->log(Logger::DEBUG, sprintf('Variation mapped to experiment "%s" has been removed for user "%s".', $experimentKey, $userId));
            return true;
        }

        $variation = $projectConfig->getVariationFromKey($experimentKey, $variationKey);
        $variationId = $variation->getId();

        // check if the variation exists in the datafile (a new variation is returned if it is not in the datafile)
        if (strlen($variationId) == 0) {
            // this case is logged in getVariationFromKey
            return false;
        }

        $this->_forcedVariationMap[$userId][$experimentId] = $variationId;
        $this->_logger->log(Logger::DEBUG, sprintf('Set variation "%s" for experiment "%s" and user "%s" in the forced variation map.', $variationId, $experimentId, $userId));
        return true;
    }

    /**
     * Determine variation the user has been forced into.
     *
     * @param $projectConfig ProjectConfigInterface  ProjectConfigInterface instance.
     * @param $experiment    Experiment     Experiment in which user is to be bucketed.
     * @param $userId        string         string
     *
     * @return null|Variation Representing the variation the user is forced into.
     */
    private function getWhitelistedVariation(ProjectConfigInterface $projectConfig, Experiment $experiment, $userId, &$decideReasons = [])
    {
        // Check if user is whitelisted for a variation.
        $forcedVariations = $experiment->getForcedVariations();
        if (!is_null($forcedVariations) && isset($forcedVariations[$userId])) {
            $variationKey = $forcedVariations[$userId];
            $variation = $projectConfig->getVariationFromKey($experiment->getKey(), $variationKey);
            if ($variationKey && !empty($variation->getKey())) {
                $message = sprintf('User "%s" is forced in variation "%s" of experiment "%s".', $userId, $variationKey, $experiment->getKey());

                $this->_logger->log(Logger::INFO, $message);
                $decideReasons[] = $message;
            } else {
                return null;
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
    private function getStoredUserProfile($userId, &$decideReasons = [])
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
            $message = sprintf('The User Profile Service lookup method failed: %s.', $e->getMessage());
            $this->_logger->log(Logger::ERROR, $message);
            $decideReasons[] = $message;
        }

        return null;
    }

    /**
     * Get the stored variation for the given experiment from the user profile.
     *
     * @param $projectConfig ProjectConfigInterface  ProjectConfigInterface instance.
     * @param $experiment    Experiment     The experiment for which we are getting the stored variation.
     * @param $userProfile   UserProfile    The user profile from which we are getting the stored variation.
     *
     * @return null|Variation the stored variation or null if not found.
     */
    private function getStoredVariation(ProjectConfigInterface $projectConfig, Experiment $experiment, UserProfile $userProfile, &$decideReasons = [])
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
        
        $variation = $projectConfig->getVariationFromId($experimentKey, $variationId);
        if (!($variation->getId())) {
            $message = sprintf(
                'User "%s" was previously bucketed into variation with ID "%s" for experiment "%s", but no matching variation was found for that user. We will re-bucket the user.',
                $userId,
                $variationId,
                $experimentKey
            );

            $this->_logger->log(
                Logger::INFO,
                $message
            );

            $decideReasons[] = $message;

            return null;
        }

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
    private function saveVariation(Experiment $experiment, Variation $variation, UserProfile $userProfile, &$decideReasons = [])
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
            $message = sprintf(
                'Saved variation "%s" of experiment "%s" for user "%s".',
                $variation->getKey(),
                $experiment->getKey(),
                $userProfile->getUserId()
            );

            $this->_logger->log(Logger::INFO, $message);
            $decideReasons[] = $message;
        } catch (Exception $e) {
            $message = sprintf(
                'Failed to save variation "%s" of experiment "%s" for user "%s".',
                $variation->getKey(),
                $experiment->getKey(),
                $userProfile->getUserId()
            );

            $this->_logger->log(Logger::WARNING, $message);
            $decideReasons[] = $message;
        }
    }
}
