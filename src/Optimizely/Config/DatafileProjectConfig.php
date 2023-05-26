<?php
/**
 * Copyright 2019-2021, 2023 Optimizely
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

namespace Optimizely\Config;

use Exception;
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
use Optimizely\Exceptions\InvalidDatafileVersionException;
use Optimizely\Exceptions\InvalidEventException;
use Optimizely\Exceptions\InvalidExperimentException;
use Optimizely\Exceptions\InvalidFeatureFlagException;
use Optimizely\Exceptions\InvalidFeatureVariableException;
use Optimizely\Exceptions\InvalidGroupException;
use Optimizely\Exceptions\InvalidInputException;
use Optimizely\Exceptions\InvalidRolloutException;
use Optimizely\Exceptions\InvalidVariationException;
use Optimizely\Logger\LoggerInterface;
use Optimizely\Logger\DefaultLogger;
use Optimizely\Optimizely;
use Optimizely\Utils\ConditionDecoder;
use Optimizely\Utils\ConfigParser;
use Optimizely\Utils\Errors;
use Optimizely\Utils\Validator;

/**
 * Class DatafileProjectConfig
 *
 * @package Optimizely
 */
class DatafileProjectConfig implements ProjectConfigInterface
{
    const RESERVED_ATTRIBUTE_PREFIX = '$opt_';
    const V2 = '2';
    const V3 = '3';
    const V4 = '4';

    /**
     * @var string Version of the datafile.
     */
    private $_version;

    /**
     * @var string Account ID of the account using the SDK.
     */
    private $_accountId;

    /**
     * @var string Project ID of the Feature Experimentation project.
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
     * @var string datafile.
     */
    private $datafile;

    /**
     * @var string environmentKey of the config.
     */
    private $environmentKey;

    /**
     * @var string sdkKey of the config.
     */
    private $sdkKey;

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
     * @var array Associative array of experiment id to associative array of variation ID to variations.
     */
    private $_variationIdMapByExperimentId;

    /**
     * @var array Associative array of experiment id to associative array of variation key to variations.
     */
    private $_variationKeyMapByExperimentId;
    
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
     * list of Attributes that will be parsed from the datafile
     *
     * @var [Attribute]
     */
    private $attributes;

    /**
     * list of Audiences that will be parsed from the datafile
     *
     * @var [Audience]
     */
    private $audiences;

    /**
     * list of Events that will be parsed from the datafile
     *
     * @var [Event]
     */
    private $events;

    /**
     * list of Typed Audiences that will be parsed from the datafile
     *
     * @var [Audience]
     */
    private $typedAudiences;

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
     * Associative array of experiment ID to Feature ID(s) in the datafile.
     *
     * @var <String, Array>
     */
    private $_experimentFeatureMap;

    /**
     * Boolean indicating if flag decisions should be sent to server or not
     *
     * @return boolean
     */
    private $_sendFlagDecisions;

    /**
     * Map indicating variations of flag decisions
     *
     * @return map
     */
    private $_flagVariationsMap;

    /**
     * DatafileProjectConfig constructor to load and set project configuration data.
     *
     * @param $datafile string JSON string representing the project.
     * @param $logger LoggerInterface
     * @param $errorHandler ErrorHandlerInterface
     */
    public function __construct($datafile, $logger, $errorHandler)
    {
        $supportedVersions = array(self::V2, self::V3, self::V4);
        $config = json_decode($datafile, true);
        $this->datafile = $datafile;
        $this->_logger = $logger;
        $this->_errorHandler = $errorHandler;
        $this->_version = $config['version'];
        $this->environmentKey = isset($config['environmentKey']) ? $config['environmentKey'] : '';
        $this->sdkKey = isset($config['sdkKey']) ? $config['sdkKey'] : '';
        if (!in_array($this->_version, $supportedVersions)) {
            throw new InvalidDatafileVersionException(
                "This version of the PHP SDK does not support the given datafile version: {$this->_version}."
            );
        }

        $this->_accountId = $config['accountId'];
        $this->_projectId = $config['projectId'];
        $this->_anonymizeIP = isset($config['anonymizeIP'])? $config['anonymizeIP'] : false;
        $this->_botFiltering = isset($config['botFiltering'])? $config['botFiltering'] : null;
        $this->_revision = $config['revision'];
        $this->_sendFlagDecisions = isset($config['sendFlagDecisions']) ? $config['sendFlagDecisions'] : false;

        $groups = $config['groups'] ?: [];
        $experiments = $config['experiments'] ?: [];
        $this->attributes = isset($config['attributes']) ? $config['attributes'] : [];
        $this->audiences = isset($config['audiences']) ? $config['audiences'] : [];
        $this->events = isset($config['events']) ? $config['events'] : [];
        $this->typedAudiences = isset($config['typedAudiences']) ? $config['typedAudiences'] : [];
        $rollouts = isset($config['rollouts']) ? $config['rollouts'] : [];
        $featureFlags = isset($config['featureFlags']) ? $config['featureFlags']: [];

        // JSON type is represented in datafile as a subtype of string for the sake of backwards compatibility.
        // Converting it to a first-class json type while creating Project Config
        foreach ($featureFlags as $featureFlagKey => $featureFlag) {
            foreach ($featureFlag['variables'] as $variableKey => $variable) {
                if (isset($variable['subType']) && $variable['type'] === FeatureVariable::STRING_TYPE && $variable['subType'] === FeatureVariable::JSON_TYPE) {
                    $variable['type'] = FeatureVariable::JSON_TYPE;
                    unset($variable['subType']);
                    $featureFlags[$featureFlagKey]['variables'][$variableKey] = $variable;
                }
            }
        }

        $this->_groupIdMap = ConfigParser::generateMap($groups, 'id', Group::class);
        $this->_experimentIdMap = ConfigParser::generateMap($experiments, 'id', Experiment::class);
        $this->_eventKeyMap = ConfigParser::generateMap($this->events, 'key', Event::class);
        $this->_attributeKeyMap = ConfigParser::generateMap($this->attributes, 'key', Attribute::class);
        $typedAudienceIdMap = ConfigParser::generateMap($this->typedAudiences, 'id', Audience::class);
        $this->_audienceIdMap = ConfigParser::generateMap($this->audiences, 'id', Audience::class);
        $this->_rollouts = ConfigParser::generateMap($rollouts, null, Rollout::class);
        $this->_featureFlags = ConfigParser::generateMap($featureFlags, null, FeatureFlag::class);

        foreach (array_values($this->_groupIdMap) as $group) {
            $experimentsInGroup = ConfigParser::generateMap($group->getExperiments(), 'id', Experiment::class);
            foreach (array_values($experimentsInGroup) as $experiment) {
                $experiment->setGroupId($group->getId());
                $experiment->setGroupPolicy($group->getPolicy());
            }
            $this->_experimentIdMap = $this->_experimentIdMap + $experimentsInGroup;
        }

        foreach ($this->_rollouts as $rollout) {
            foreach ($rollout->getExperiments() as $experiment) {
                $this->_experimentIdMap[$experiment->getId()] = $experiment;
            }
        }

        $this->_variationKeyMap = [];
        $this->_variationIdMap = [];
        $this->_variationKeyMapByExperimentId = [];
        $this->_variationIdMapByExperimentId = [];
        $this->_experimentKeyMap = [];

        foreach (array_values($this->_experimentIdMap) as $experiment) {
            $this->_variationKeyMap[$experiment->getKey()] = [];
            $this->_variationIdMap[$experiment->getKey()] = [];
            $this->_variationIdMapByExperimentId[$experiment->getId()] = [];
            $this->_variationKeyMapByExperimentId[$experiment->getId()] = [];
            $this->_experimentKeyMap[$experiment->getKey()] = $experiment;

            foreach ($experiment->getVariations() as $variation) {
                $this->_variationKeyMap[$experiment->getKey()][$variation->getKey()] = $variation;
                $this->_variationIdMap[$experiment->getKey()][$variation->getId()] = $variation;
                $this->_variationKeyMapByExperimentId[$experiment->getId()][$variation->getKey()] = $variation;
                $this->_variationIdMapByExperimentId[$experiment->getId()][$variation->getId()] = $variation;
            }
        }

        foreach (array_values($this->_audienceIdMap) as $audience) {
            $audience->setConditionsList(json_decode($audience->getConditions(), true));
        }

        // Conditions in typedAudiences are not expected to be string-encoded so they don't need
        // to be decoded unlike audiences.
        foreach (array_values($typedAudienceIdMap) as $typedAudience) {
            $typedAudience->setConditionsList($typedAudience->getConditions());
        }

        // Overwrite audiences by typedAudiences.
        $this->_audienceIdMap = array_replace($this->_audienceIdMap, $typedAudienceIdMap);

        $rolloutVariationIdMap = [];
        $rolloutVariationKeyMap = [];
        $rolloutVariationIdMapByExperimentId = [];
        $rolloutVariationKeyMapByExperimentId = [];
        foreach ($this->_rollouts as $rollout) {
            $this->_rolloutIdMap[$rollout->getId()] = $rollout;

            foreach ($rollout->getExperiments() as $rule) {
                $rolloutVariationIdMap[$rule->getKey()] = [];
                $rolloutVariationKeyMap[$rule->getKey()] = [];
                $rolloutVariationIdMapByExperimentId[$rule->getId()] = [];
                $rolloutVariationKeyMapByExperimentId[$rule->getId()] = [];

                $variations = $rule->getVariations();
                foreach ($variations as $variation) {
                    $rolloutVariationIdMap[$rule->getKey()][$variation->getId()] = $variation;
                    $rolloutVariationKeyMap[$rule->getKey()][$variation->getKey()] = $variation;
                    $rolloutVariationIdMapByExperimentId[$rule->getId()][$variation->getId()] = $variation;
                    $rolloutVariationKeyMapByExperimentId[$rule->getId()][$variation->getKey()] = $variation;
                }
            }
        }
        $this->_flagVariationsMap = array();
        foreach ($this->_featureFlags as $flag) {
            $flagVariations = array();
            $flagRules = $this->getAllRulesForFlag($flag);

            foreach ($flagRules as $rule) {
                $filtered_variations = [];
                foreach (array_values($rule->getVariations()) as $variation) {
                    $exist = false;
                    foreach ($flagVariations as $flagVariation) {
                        if ($flagVariation->getId() == $variation->getId()) {
                            $exist = true;
                            break;
                        }
                    }
                    if (!$exist) {
                        array_push($filtered_variations, $variation);
                    }
                }
                $flagVariations = array_merge($flagVariations, $filtered_variations);
            }

            $this->_flagVariationsMap[$flag->getKey()] = $flagVariations;
        }
        // Add variations for rollout experiments to variationIdMap and variationKeyMap
        $this->_variationIdMap = $this->_variationIdMap + $rolloutVariationIdMap;
        $this->_variationKeyMap = $this->_variationKeyMap + $rolloutVariationKeyMap;
        $this->_variationIdMapByExperimentId = $this->_variationIdMapByExperimentId + $rolloutVariationIdMapByExperimentId;
        $this->_variationKeyMapByExperimentId = $this->_variationKeyMapByExperimentId + $rolloutVariationKeyMapByExperimentId;

        foreach (array_values($this->_featureFlags) as $featureFlag) {
            $this->_featureKeyMap[$featureFlag->getKey()] = $featureFlag;
        }

        $this->_experimentFeatureMap = [];
        if ($this->_featureKeyMap) {
            foreach ($this->_featureKeyMap as $featureKey => $featureFlag) {
                $this->_featureFlagVariableMap[$featureKey] = ConfigParser::generateMap(
                    $featureFlag->getVariables(),
                    'key',
                    FeatureVariable::class
                );

                $featureFlagId = $featureFlag->getId();
                foreach ($featureFlag->getExperimentIds() as $experimentId) {
                    $this->_experimentFeatureMap[$experimentId] = [$featureFlagId];
                }
            }
        }
    }

    private function getAllRulesForFlag(FeatureFlag $flag)
    {
        $rules = array();
        foreach ($flag->getExperimentIds() as $experimentId) {
            array_push($rules, $this->_experimentIdMap[$experimentId]);
        }
        if ($this->_rolloutIdMap && key_exists($flag->getRolloutId(), $this->_rolloutIdMap)) {
            $rollout = $this->_rolloutIdMap[$flag->getRolloutId()];
            $rules = array_merge($rules, $rollout->getExperiments());
        }
        return $rules;
    }
    /**
     * Create ProjectConfig based on datafile string.
     *
     * @param string                $datafile           JSON string representing the Optimizely project.
     * @param bool                  $skipJsonValidation boolean representing whether JSON schema validation needs to be performed.
     * @param LoggerInterface       $logger             Logger instance
     * @param ErrorHandlerInterface $errorHandler       ErrorHandler instance.
     * @return ProjectConfigInterface ProjectConfig instance or null;
     */
    public static function createProjectConfigFromDatafile($datafile, $skipJsonValidation, $logger, $errorHandler)
    {
        if (!$skipJsonValidation) {
            if (!Validator::validateJsonSchema($datafile)) {
                $defaultLogger = new DefaultLogger();
                $defaultLogger->log(Logger::ERROR, 'Provided "datafile" has invalid schema.');
                $logger->log(Logger::ERROR, 'Provided "datafile" has invalid schema.');
                return null;
            }
        }

        try {
            $config = new DatafileProjectConfig($datafile, $logger, $errorHandler);
        } catch (Exception $exception) {
            $defaultLogger = new DefaultLogger();
            $errorMsg = $exception instanceof InvalidDatafileVersionException ? $exception->getMessage() : sprintf(Errors::INVALID_FORMAT, 'datafile');
            $errorToHandle = $exception instanceof InvalidDatafileVersionException ? new InvalidDatafileVersionException($errorMsg) : new InvalidInputException($errorMsg);
            $defaultLogger->log(Logger::ERROR, $errorMsg);
            $logger->log(Logger::ERROR, $errorMsg);
            $errorHandler->handleError($errorToHandle);
            return null;
        }

        return $config;
    }

    /**
     * @return string String representing contents of datafile.
     */
    public function toDatafile()
    {
        return $this->datafile;
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
     * @return string Config environmentKey.
     */
    public function getEnvironmentKey()
    {
        return $this->environmentKey;
    }

    /**
     * @return string Config sdkKey.
     */
    public function getSdkKey()
    {
        return $this->sdkKey;
    }

    /**
     * @return array List of feature flags parsed from the datafile
     */
    public function getFeatureFlags()
    {
        return $this->_featureFlags;
    }

    /**
     * @return array List of attributes parsed from the datafile
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return array List of audiences parsed from the datafile
     */
    public function getAudiences()
    {
        return $this->audiences;
    }

    /**
     * @return array List of events parsed from the datafile
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @return array List of typed audiences parsed from the datafile
     */
    public function getTypedAudiences()
    {
        return $this->typedAudiences;
    }

    /**
     * @return array List of all experiments (including group experiments)
     *               parsed from the datafile
     */
    public function getAllExperiments()
    {
        // Exclude rollout experiments
        $rolloutExperimentIds = [];
        foreach ($this->_rollouts as $rollout) {
            foreach ($rollout->getExperiments() as $experiment) {
                $rolloutExperimentIds[] = $experiment->getId();
            }
        }
        return array_filter(array_values($this->_experimentIdMap), function ($experiment) use ($rolloutExperimentIds) {
            return !in_array($experiment->getId(), $rolloutExperimentIds);
        });
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
     * Gets the variation associated with experiment or rollout in instance of given feature flag key
     *
     * @param string Feature flag key
     * @param string variation key
     *
     * @return Variation / null
     */
    public function getFlagVariationByKey($flagKey, $variationKey)
    {
        if (array_key_exists($flagKey, $this->_flagVariationsMap)) {
            foreach ($this->_flagVariationsMap[$flagKey] as $variation) {
                if ($variation->getKey() == $variationKey) {
                    return $variation;
                }
            }
        }
        return null;
    }

    /**
     * @param String $featureKey Key of the feature flag
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
     * @param String $rolloutId
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
     *         Null is returned if ID is invalid.
     */
    public function getAudience($audienceId)
    {
        if (isset($this->_audienceIdMap[$audienceId])) {
            return $this->_audienceIdMap[$audienceId];
        }

        $this->_logger->log(Logger::ERROR, sprintf('Audience ID "%s" is not in datafile.', $audienceId));
        $this->_errorHandler->handleError(new InvalidAudienceException('Provided audience is not in datafile.'));

        return null;
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
     * @param $experimentId string ID for experiment.
     * @param $variationId string ID for variation.
     *
     * @return Variation Entity corresponding to the provided experiment ID and variation ID.
     *         Dummy entity is returned if key or ID is invalid.
     */
    public function getVariationFromIdByExperimentId($experimentId, $variationId)
    {
        if (isset($this->_variationIdMapByExperimentId[$experimentId])
            && isset($this->_variationIdMapByExperimentId[$experimentId][$variationId])
        ) {
            return $this->_variationIdMapByExperimentId[$experimentId][$variationId];
        }

        $this->_logger->log(
            Logger::ERROR,
            sprintf(
                'No variation ID "%s" defined in datafile for experiment "%s".',
                $variationId,
                $experimentId
            )
        );
        $this->_errorHandler->handleError(new InvalidVariationException('Provided variation is not in datafile.'));
        return new Variation();
    }

    /**
     * @param $experimentId string ID for experiment.
     * @param $variationKey string Key for variation.
     *
     * @return Variation Entity corresponding to the provided experiment ID and variation Key.
     *         Dummy entity is returned if key or ID is invalid.
     */
    public function getVariationFromKeyByExperimentId($experimentId, $variationKey)
    {
        if (isset($this->_variationKeyMapByExperimentId[$experimentId])
            && isset($this->_variationKeyMapByExperimentId[$experimentId][$variationKey])
        ) {
            return $this->_variationKeyMapByExperimentId[$experimentId][$variationKey];
        }

        $this->_logger->log(
            Logger::ERROR,
            sprintf(
                'No variation Key "%s" defined in datafile for experiment "%s".',
                $variationKey,
                $experimentId
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

    /**
     * Determines if given experiment is a feature test.
     *
     * @param string Experiment ID.
     *
     * @return boolean A boolean value that indicates if the experiment is a feature test.
     */
    public function isFeatureExperiment($experimentId)
    {
        return array_key_exists($experimentId, $this->_experimentFeatureMap);
    }

    /**
     * Returns map array of Flag key as key and Variations as value
     *
     * @return array
     */
    public function getFlagVariationsMap()
    {
        return $this->_flagVariationsMap;
    }

    /**
     * Returns if flag decisions should be sent to server or not
     *
     * @return boolean
     */
    public function getSendFlagDecisions()
    {
        return $this->_sendFlagDecisions;
    }
}
