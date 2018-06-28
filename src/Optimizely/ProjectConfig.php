<?php
/**
 * Copyright 2016, 2018, Optimizely
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
use Optimizely\Entity\Attribute;
use Optimizely\Entity\Audience;
use Optimizely\Entity\Event;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\FeatureFlag;
use Optimizely\Entity\FeatureVariable;
use Optimizely\Entity\Group;
use Optimizely\Entity\Rollout;
use Optimizely\Entity\Variation;
use Optimizely\Enums\ControlAttributes;
use Optimizely\ErrorHandler\ErrorHandlerInterface;
use Optimizely\Exceptions\InvalidAttributeException;
use Optimizely\Exceptions\InvalidAudienceException;
use Optimizely\Exceptions\InvalidEventException;
use Optimizely\Exceptions\InvalidExperimentException;
use Optimizely\Exceptions\InvalidFeatureFlagException;
use Optimizely\Exceptions\InvalidFeatureVariableException;
use Optimizely\Exceptions\InvalidGroupException;
use Optimizely\Exceptions\InvalidRolloutException;
use Optimizely\Exceptions\InvalidVariationException;
use Optimizely\Logger\LoggerInterface;
use Optimizely\Utils\ConditionDecoder;
use Optimizely\Utils\ConfigParser;

/**
 * Class ProjectConfig
 *
 * @package Optimizely
 */
class ProjectConfig
{
    const RESERVED_ATTRIBUTE_PREFIX = '$opt_';

    /**
     * @var string Version of the datafile.
     */
    private $_version;

    /**
     * @var string Account ID of the account using the SDK.
     */
    private $_accountId;

    /**
     * @var string Project ID of the Full Stack project.
     */
    private $_projectId;

    /**
     * @var boolean denotes if Optimizely should remove the
     * last block of visitors' IP address before storing event data
     */
    private $_anonymizeIP;

    /**
     * @var boolean denotes if Optimizely should perform
     * bot filtering on your dispatched events.
     */
    private $_botFiltering;

    /**
     * @var string Revision of the datafile.
     */
    private $_revision;

    /**
     * @var array Associative array of group ID to Group(s) in the datafile.
     */
    private $_groupIdMap;

    /**
     * @var array Associative array of experiment key to Experiment(s) in the datafile.
     */
    private $_experimentKeyMap;

    /**
     * @var array Associative array of experiment ID to Experiment(s) in the datafile.
     */
    private $_experimentIdMap;

    /**
     * @var array Associative array of experiment key to associative array of variation key to variations.
     */
    private $_variationKeyMap;

    /**
     * @var array Associative array of experiment key to associative array of variation ID to variations.
     */
    private $_variationIdMap;

    /**
     * @var array Associative array of event key to Event(s) in the datafile.
     */
    private $_eventKeyMap;

    /**
     * @var array Associative array of attribute key to Attribute(s) in the datafile.
     */
    private $_attributeKeyMap;

    /**
     * @var array Associative array of audience ID to Audience(s) in the datafile.
     */
    private $_audienceIdMap;

    /**
     * @var LoggerInterface Logger for logging messages.
     */
    private $_logger;

    /**
     * @var ErrorHandlerInterface Handler for exceptions.
     */
    private $_errorHandler;

    /**
     * @var array Associative array of user IDs to an associative array
     * of experiments to variations. This contains all the forced variations
     * set by the user by calling setForcedVariation (it is not the same as the
     * whitelisting forcedVariations data structure in the Experiments class).
     */
    private $_forcedVariationMap;

    /**
     * list of Feature Flags that will be parsed from the datafile.
     *
     * @var [FeatureFlag]
     */
    private $_featureFlags;

    /**
     * list of Rollouts that will be parsed from the datafile
     *
     * @var [Rollout]
     */
    private $_rollouts;

    /**
     * internal mapping of feature keys to feature flag models.
     *
     * @var <String, FeatureFlag>  associative array of feature keys to feature flags
     */
    private $_featureKeyMap;

    /**
     * internal mapping of rollout IDs to Rollout models.
     *
     * @var <String, Rollout>  associative array of rollout ids to rollouts
     */
    private $_rolloutIdMap;

    /**
     * Feature Flag key to Feature Variable key to Feature Variable map
     *
     * @var <String, <String, FeatureVariable>>
     */
    private $_featureFlagVariableMap;

    /**
     * ProjectConfig constructor to load and set project configuration data.
     *
     * @param $datafile string JSON string representing the project.
     * @param $logger LoggerInterface
     * @param $errorHandler ErrorHandlerInterface
     */
    public function __construct($datafile, $logger, $errorHandler)
    {
        $config = json_decode($datafile, true);
        $this->_logger = $logger;
        $this->_errorHandler = $errorHandler;
        $this->_version = $config['version'];
        $this->_accountId = $config['accountId'];
        $this->_projectId = $config['projectId'];
        $this->_anonymizeIP = isset($config['anonymizeIP'])? $config['anonymizeIP'] : false;
        $this->_botFiltering = isset($config['botFiltering'])? $config['botFiltering'] : null;
        $this->_revision = $config['revision'];
        $this->_forcedVariationMap = [];

        $groups = $config['groups'] ?: [];
        $experiments = $config['experiments'] ?: [];
        $events = $config['events'] ?: [];
        $attributes = $config['attributes'] ?: [];
        $audiences = $config['audiences'] ?: [];
        $rollouts = isset($config['rollouts']) ? $config['rollouts'] : [];
        $featureFlags = isset($config['featureFlags']) ? $config['featureFlags']: [];

        $this->_groupIdMap = ConfigParser::generateMap($groups, 'id', Group::class);
        $this->_experimentKeyMap = ConfigParser::generateMap($experiments, 'key', Experiment::class);
        $this->_eventKeyMap = ConfigParser::generateMap($events, 'key', Event::class);
        $this->_attributeKeyMap = ConfigParser::generateMap($attributes, 'key', Attribute::class);
        $this->_audienceIdMap = ConfigParser::generateMap($audiences, 'id', Audience::class);
        $this->_rollouts = ConfigParser::generateMap($rollouts, null, Rollout::class);
        $this->_featureFlags = ConfigParser::generateMap($featureFlags, null, FeatureFlag::class);

        foreach (array_values($this->_groupIdMap) as $group) {
            $experimentsInGroup = ConfigParser::generateMap($group->getExperiments(), 'key', Experiment::class);
            foreach (array_values($experimentsInGroup) as $experiment) {
                $experiment->setGroupId($group->getId());
                $experiment->setGroupPolicy($group->getPolicy());
            }
            $this->_experimentKeyMap = $this->_experimentKeyMap + $experimentsInGroup;
        }

        $this->_variationKeyMap = [];
        $this->_variationIdMap = [];
        $this->_experimentIdMap = [];

        foreach (array_values($this->_experimentKeyMap) as $experiment) {
            $this->_variationKeyMap[$experiment->getKey()] = [];
            $this->_variationIdMap[$experiment->getKey()] = [];
            $this->_experimentIdMap[$experiment->getId()] = $experiment;

            foreach ($experiment->getVariations() as $variation) {
                $this->_variationKeyMap[$experiment->getKey()][$variation->getKey()] = $variation;
                $this->_variationIdMap[$experiment->getKey()][$variation->getId()] = $variation;
            }
        }

        $conditionDecoder = new ConditionDecoder();
        foreach (array_values($this->_audienceIdMap) as $audience) {
            $conditionDecoder->deserializeAudienceConditions($audience->getConditions());
            $audience->setConditionsList($conditionDecoder->getConditionsList());
        }

        $rolloutVariationIdMap = [];
        $rolloutVariationKeyMap = [];
        foreach ($this->_rollouts as $rollout) {
            $this->_rolloutIdMap[$rollout->getId()] = $rollout;

            foreach ($rollout->getExperiments() as $rule) {
                $rolloutVariationIdMap[$rule->getKey()] = [];
                $rolloutVariationKeyMap[$rule->getKey()] = [];

                $variations = $rule->getVariations();
                foreach ($variations as $variation) {
                    $rolloutVariationIdMap[$rule->getKey()][$variation->getId()] = $variation;
                    $rolloutVariationKeyMap[$rule->getKey()][$variation->getKey()] = $variation;
                }
            }
        }

        // Add variations for rollout experiments to variationIdMap and variationKeyMap
        $this->_variationIdMap = $this->_variationIdMap + $rolloutVariationIdMap;
        $this->_variationKeyMap = $this->_variationKeyMap + $rolloutVariationKeyMap;

        foreach (array_values($this->_featureFlags) as $featureFlag) {
            $this->_featureKeyMap[$featureFlag->getKey()] = $featureFlag;
        }

        if ($this->_featureKeyMap) {
            foreach ($this->_featureKeyMap as $featureKey => $featureFlag) {
                $this->_featureFlagVariableMap[$featureKey] = ConfigParser::generateMap(
                    $featureFlag->getVariables(),
                    'key',
                    FeatureVariable::class
                );
            }
        }
    }

    /**
     * @return string String representing account ID from the datafile.
     */
    public function getAccountId()
    {
        return $this->_accountId;
    }

    /**
     * @return string String representing project ID from the datafile.
     */
    public function getProjectId()
    {
        return $this->_projectId;
    }

    /**
     * @return boolean Flag denoting if Optimizely should remove last block
     * of visitors' IP address before storing event data
     */
    public function getAnonymizeIP()
    {
        return $this->_anonymizeIP;
    }

    /**
     * @return boolean Flag denoting if Optimizely should perform
     * bot filtering on your dispatched events.
     */
    public function getBotFiltering()
    {
        return $this->_botFiltering;
    }

    /**
     * @return string String representing revision of the datafile.
     */
    public function getRevision()
    {
        return $this->_revision;
    }

    /**
     * @return array List of feature flags parsed from the datafile
     */
    public function getFeatureFlags()
    {
        return $this->_featureFlags;
    }

    /**
     * @param $groupId string ID of the group.
     *
     * @return Group Entity corresponding to the ID.
     *         Dummy entity is returned if ID is invalid.
     */
    public function getGroup($groupId)
    {
        if (isset($this->_groupIdMap[$groupId])) {
            return $this->_groupIdMap[$groupId];
        }

        $this->_logger->log(Logger::ERROR, sprintf('Group ID "%s" is not in datafile.', $groupId));
        $this->_errorHandler->handleError(new InvalidGroupException('Provided group is not in datafile.'));
        return new Group();
    }

    /**
     * @param $experimentKey string Key of the experiment.
     *
     * @return Experiment Entity corresponding to the key.
     *         Dummy entity is returned if key is invalid.
     */
    public function getExperimentFromKey($experimentKey)
    {
        if (isset($this->_experimentKeyMap[$experimentKey])) {
            return $this->_experimentKeyMap[$experimentKey];
        }

        $this->_logger->log(Logger::ERROR, sprintf('Experiment key "%s" is not in datafile.', $experimentKey));
        $this->_errorHandler->handleError(new InvalidExperimentException('Provided experiment is not in datafile.'));
        return new Experiment();
    }

    /**
     * @param $experimentId string ID of the experiment.
     *
     * @return Experiment Entity corresponding to the key.
     *         Dummy entity is returned if ID is invalid.
     */
    public function getExperimentFromId($experimentId)
    {
        if (isset($this->_experimentIdMap[$experimentId])) {
            return $this->_experimentIdMap[$experimentId];
        }

        $this->_logger->log(Logger::ERROR, sprintf('Experiment ID "%s" is not in datafile.', $experimentId));
        $this->_errorHandler->handleError(new InvalidExperimentException('Provided experiment is not in datafile.'));
        return new Experiment();
    }

    /**
     * @param  String $featureKey Key of the feature flag
     *
     * @return FeatureFlag Entity corresponding to the key.
     */
    public function getFeatureFlagFromKey($featureKey)
    {
        if (isset($this->_featureKeyMap[$featureKey])) {
            return $this->_featureKeyMap[$featureKey];
        }

        $this->_logger->log(Logger::ERROR, sprintf('FeatureFlag Key "%s" is not in datafile.', $featureKey));
        $this->_errorHandler->handleError(new InvalidFeatureFlagException('Provided feature flag is not in datafile.'));
        return new FeatureFlag();
    }

    /**
     * @param  String $rolloutId
     *
     * @return Rollout
     */
    public function getRolloutFromId($rolloutId)
    {
        if (isset($this->_rolloutIdMap[$rolloutId])) {
            return $this->_rolloutIdMap[$rolloutId];
        }

        $this->_logger->log(Logger::ERROR, sprintf('Rollout with ID "%s" is not in the datafile.', $rolloutId));

        $this->_errorHandler->handleError(new InvalidRolloutException('Provided rollout is not in datafile.'));
        return new Rollout();
    }

    /**
     * @param $eventKey string Key of the event.
     *
     * @return Event Entity corresponding to the key.
     *         Dummy entity is returned if key is invalid.
     */
    public function getEvent($eventKey)
    {
        if (isset($this->_eventKeyMap[$eventKey])) {
            return $this->_eventKeyMap[$eventKey];
        }

        $this->_logger->log(Logger::ERROR, sprintf('Event key "%s" is not in datafile.', $eventKey));
        $this->_errorHandler->handleError(new InvalidEventException('Provided event is not in datafile.'));
        return new Event();
    }

    /**
     * @param $audienceId string ID of the audience.
     *
     * @return Audience Entity corresponding to the ID.
     *         Dummy entity is returned if ID is invalid.
     */
    public function getAudience($audienceId)
    {
        if (isset($this->_audienceIdMap[$audienceId])) {
            return $this->_audienceIdMap[$audienceId];
        }

        $this->_logger->log(Logger::ERROR, sprintf('Audience ID "%s" is not in datafile.', $audienceId));
        $this->_errorHandler->handleError(new InvalidAudienceException('Provided audience is not in datafile.'));
        return new Audience();
    }

    /**
     * @param $attributeKey string Key of the attribute.
     *
     * @return Attribute Entity corresponding to the key.
     *         Null is returned if key is invalid.
     */
    public function getAttribute($attributeKey)
    {
        $hasReservedPrefix = strpos($attributeKey, self::RESERVED_ATTRIBUTE_PREFIX) === 0;

        if (isset($this->_attributeKeyMap[$attributeKey])) {
            if ($hasReservedPrefix) {
                $this->_logger->log(
                    Logger::WARNING,
                    sprintf('Attribute %s unexpectedly has reserved prefix %s; using attribute ID instead of reserved attribute name.', $attributeKey, self::RESERVED_ATTRIBUTE_PREFIX)
                );
            }

            return $this->_attributeKeyMap[$attributeKey];
        }

        if ($hasReservedPrefix) {
            return new Attribute($attributeKey, $attributeKey);
        }

        $this->_logger->log(Logger::ERROR, sprintf('Attribute key "%s" is not in datafile.', $attributeKey));
        $this->_errorHandler->handleError(new InvalidAttributeException('Provided attribute is not in datafile.'));
        
        return null;
    }

    /**
     * @param $experimentKey string Key for experiment.
     * @param $variationKey string Key for variation.
     *
     * @return Variation Entity corresponding to the provided experiment key and variation key.
     *         Dummy entity is returned if key or ID is invalid.
     */
    public function getVariationFromKey($experimentKey, $variationKey)
    {
        if (isset($this->_variationKeyMap[$experimentKey])
            && isset($this->_variationKeyMap[$experimentKey][$variationKey])
        ) {
            return $this->_variationKeyMap[$experimentKey][$variationKey];
        }

        $this->_logger->log(
            Logger::ERROR,
            sprintf(
                'No variation key "%s" defined in datafile for experiment "%s".',
                $variationKey,
                $experimentKey
            )
        );
        $this->_errorHandler->handleError(new InvalidVariationException('Provided variation is not in datafile.'));
        return new Variation();
    }

    /**
     * @param $experimentKey string Key for experiment.
     * @param $variationId string ID for variation.
     *
     * @return Variation Entity corresponding to the provided experiment key and variation ID.
     *         Dummy entity is returned if key or ID is invalid.
     */
    public function getVariationFromId($experimentKey, $variationId)
    {
        if (isset($this->_variationIdMap[$experimentKey])
            && isset($this->_variationIdMap[$experimentKey][$variationId])
        ) {
            return $this->_variationIdMap[$experimentKey][$variationId];
        }

        $this->_logger->log(
            Logger::ERROR,
            sprintf(
                'No variation ID "%s" defined in datafile for experiment "%s".',
                $variationId,
                $experimentKey
            )
        );
        $this->_errorHandler->handleError(new InvalidVariationException('Provided variation is not in datafile.'));
        return new Variation();
    }

    /**
     * Gets the feature variable instance given feature flag key and variable key
     *
     * @param string Feature flag key
     * @param string Variable key
     *
     * @return FeatureVariable / null
     */
    public function getFeatureVariableFromKey($featureFlagKey, $variableKey)
    {
        $featureFlag = $this->getFeatureFlagFromKey($featureFlagKey);
        if ($featureFlag && !($featureFlag->getKey())) {
            return null;
        }

        if (isset($this->_featureFlagVariableMap[$featureFlagKey])
            && isset($this->_featureFlagVariableMap[$featureFlagKey][$variableKey])
        ) {
            return $this->_featureFlagVariableMap[$featureFlagKey][$variableKey];
        }

        $this->_logger->log(
            Logger::ERROR,
   

            sprintf(
                'No variable key "%s" defined in datafile for feature flag "%s".',
                $variableKey,
                $featureFlagKey
            )
        );
        $this->_errorHandler->handleError(
            new InvalidFeatureVariableException('Provided feature variable is not in datafile.')
        );
        return null;
    }

    public function isVariationIdValid($experimentKey, $variationId)
    {
        return isset($this->_variationIdMap[$experimentKey]) &&
            isset($this->_variationIdMap[$experimentKey][$variationId]);
    }

    /**
     * Gets the forced variation key for the given user and experiment.
     *
     * @param $experimentKey string Key for experiment.
     * @param $userId string The user Id.
     *
     * @return Variation The variation which the given user and experiment should be forced into.
     */
    public function getForcedVariation($experimentKey, $userId)
    {

        // check for null and empty string user ID
        if (strlen($userId) == 0) {
            $this->_logger->log(Logger::DEBUG, 'User ID is invalid');
            return null;
        }

        if (!isset($this->_forcedVariationMap[$userId])) {
            $this->_logger->log(Logger::DEBUG, sprintf('User "%s" is not in the forced variation map.', $userId));
            return null;
        }

        $experimentToVariationMap = $this->_forcedVariationMap[$userId];
        $experimentId = $this->getExperimentFromKey($experimentKey)->getId();
        // check for null and empty string experiment ID
        if (strlen($experimentId) == 0) {
            // this case is logged in getExperimentFromKey
            return null;
        }

        if (!isset($experimentToVariationMap[$experimentId])) {
            $this->_logger->log(Logger::DEBUG, sprintf('No experiment "%s" mapped to user "%s" in the forced variation map.', $experimentKey, $userId));
            return null;
        }

        $variationId = $experimentToVariationMap[$experimentId];
        $variation = $this->getVariationFromId($experimentKey, $variationId);
        $variationKey = $variation->getKey();

        $this->_logger->log(Logger::DEBUG, sprintf('Variation "%s" is mapped to experiment "%s" and user "%s" in the forced variation map', $variationKey, $experimentKey, $userId));

        return $variation;
    }

    /**
     * Sets an associative array of user IDs to an associative array of experiments
     * to forced variations.
     *
     * @param $experimentKey string Key for experiment.
     * @param $userId string The user Id.
     * @param $variationKey string Key for variation. If null, then clear the existing experiment-to-variation mapping.
     *
     * @return boolean A boolean value that indicates if the set completed successfully.
     */
    public function setForcedVariation($experimentKey, $userId, $variationKey)
    {
        // check for null and empty string user ID
        if (strlen($userId) == 0) {
            $this->_logger->log(Logger::DEBUG, 'User ID is invalid');
            return false;
        }

        $experiment = $this->getExperimentFromKey($experimentKey);
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

        $variation = $this->getVariationFromKey($experimentKey, $variationKey);
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
}
