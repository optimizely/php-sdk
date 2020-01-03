<?php
/**
 * Copyright 2016-2020, Optimizely
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

use Exception;
use Optimizely\Config\DatafileProjectConfig;
use Optimizely\Exceptions\InvalidAttributeException;
use Optimizely\Exceptions\InvalidEventTagException;
use Throwable;
use Monolog\Logger;
use Optimizely\DecisionService\DecisionService;
use Optimizely\DecisionService\FeatureDecision;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\FeatureVariable;
use Optimizely\Enums\DecisionNotificationTypes;
use Optimizely\ErrorHandler\ErrorHandlerInterface;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Event\Builder\EventBuilder;
use Optimizely\Event\Dispatcher\DefaultEventDispatcher;
use Optimizely\Event\Dispatcher\EventDispatcherInterface;
use Optimizely\Logger\LoggerInterface;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Notification\NotificationCenter;
use Optimizely\Notification\NotificationType;
use Optimizely\OptimizelyConfig\OptimizelyConfigService;
use Optimizely\ProjectConfigManager\ProjectConfigManagerInterface;
use Optimizely\ProjectConfigManager\StaticProjectConfigManager;
use Optimizely\UserProfile\UserProfileServiceInterface;
use Optimizely\Utils\Errors;
use Optimizely\Utils\Validator;
use Optimizely\Utils\VariableTypeUtils;

/**
 * Class Optimizely
 *
 * @package Optimizely
 */
class Optimizely
{
    const EVENT_KEY = 'Event Key';
    const EXPERIMENT_KEY = 'Experiment Key';
    const FEATURE_FLAG_KEY = 'Feature Flag Key';
    const USER_ID = 'User ID';
    const VARIABLE_KEY = 'Variable Key';
    const VARIATION_KEY = 'Variation Key';

    /**
     * @var DatafileProjectConfig
     */
    private $_config;

    /**
     * @var DecisionService
     */
    private $_decisionService;

    /**
     * @var ErrorHandlerInterface
     */
    private $_errorHandler;

    /**
     * @var EventDispatcherInterface
     */
    private $_eventDispatcher;

    /**
     * @var EventBuilder
     */
    private $_eventBuilder;

    /**
     * @var boolean Denotes whether Optimizely object is valid or not.
     */
    private $_isValid;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var ProjectConfigManagerInterface
     */
    private $_projectConfigManager;

    /**
     * @var NotificationCenter
     */
    public $notificationCenter;

    /**
     * Optimizely constructor for managing Full Stack PHP projects.
     *
     * @param $datafile string JSON string representing the project.
     * @param $eventDispatcher EventDispatcherInterface
     * @param $logger LoggerInterface
     * @param $errorHandler ErrorHandlerInterface
     * @param $skipJsonValidation boolean representing whether JSON schema validation needs to be performed.
     * @param $userProfileService UserProfileServiceInterface
     * @param $configManager ProjectConfigManagerInterface provides ProjectConfig through getConfig method.
     * @param $notificationCenter NotificationCenter
     */
    public function __construct(
        $datafile,
        EventDispatcherInterface $eventDispatcher = null,
        LoggerInterface $logger = null,
        ErrorHandlerInterface $errorHandler = null,
        $skipJsonValidation = false,
        UserProfileServiceInterface $userProfileService = null,
        ProjectConfigManagerInterface $configManager = null,
        NotificationCenter $notificationCenter = null
    ) {
        $this->_isValid = true;
        $this->_eventDispatcher = $eventDispatcher ?: new DefaultEventDispatcher();
        $this->_logger = $logger ?: new NoOpLogger();
        $this->_errorHandler = $errorHandler ?: new NoOpErrorHandler();
        $this->_eventBuilder = new EventBuilder($this->_logger);
        $this->_decisionService = new DecisionService($this->_logger, $userProfileService);
        $this->notificationCenter = $notificationCenter ?: new NotificationCenter($this->_logger, $this->_errorHandler);
        $this->_projectConfigManager = $configManager ?: new StaticProjectConfigManager($datafile, $skipJsonValidation, $this->_logger, $this->_errorHandler);
    }

    /**
     * Returns DatafileProjectConfig instance.
     * @return DatafileProjectConfig DatafileProjectConfig instance or null
     */
    protected function getConfig()
    {
        $config = $this->_projectConfigManager->getConfig();
        return $config instanceof DatafileProjectConfig ? $config : null;
    }

    /**
     * Helper function to validate user inputs into the API methods.
     *
     * @param $attributes array Associative array of user attributes
     * @param $eventTags array Hash representing metadata associated with an event.
     *
     * @return boolean Representing whether all user inputs are valid.
     */
    private function validateUserInputs($attributes, $eventTags = null)
    {
        if (!is_null($attributes) && !Validator::areAttributesValid($attributes)) {
            $this->_logger->log(Logger::ERROR, 'Provided attributes are in an invalid format.');
            $this->_errorHandler->handleError(
                new InvalidAttributeException('Provided attributes are in an invalid format.')
            );
            return false;
        }

        if (!is_null($eventTags)) {
            if (!Validator::areEventTagsValid($eventTags)) {
                $this->_logger->log(Logger::ERROR, 'Provided event tags are in an invalid format.');
                $this->_errorHandler->handleError(
                    new InvalidEventTagException('Provided event tags are in an invalid format.')
                );
                return false;
            }
        }

        return true;
    }

    /**
     * @param  string        Experiment key
     * @param  string        Variation key
     * @param  string        User ID
     * @param  array         Associative array of user attributes
     * @param  DatafileProjectConfig DatafileProjectConfig instance
     */
    protected function sendImpressionEvent($config, $experimentKey, $variationKey, $userId, $attributes)
    {
        $impressionEvent = $this->_eventBuilder
            ->createImpressionEvent($config, $experimentKey, $variationKey, $userId, $attributes);
        $this->_logger->log(Logger::INFO, sprintf('Activating user "%s" in experiment "%s".', $userId, $experimentKey));
        $this->_logger->log(
            Logger::DEBUG,
            sprintf(
                'Dispatching impression event to URL %s with params %s.',
                $impressionEvent->getUrl(),
                json_encode($impressionEvent->getParams())
            )
        );

        try {
            $this->_eventDispatcher->dispatchEvent($impressionEvent);
        } catch (Throwable $exception) {
            $this->_logger->log(
                Logger::ERROR,
                sprintf(
                    'Unable to dispatch impression event. Error %s',
                    $exception->getMessage()
                )
            );
        } catch (Exception $exception) {
            $this->_logger->log(
                Logger::ERROR,
                sprintf(
                    'Unable to dispatch impression event. Error %s',
                    $exception->getMessage()
                )
            );
        }

        $this->notificationCenter->sendNotifications(
            NotificationType::ACTIVATE,
            array(
                $config->getExperimentFromKey($experimentKey),
                $userId,
                $attributes,
                $config->getVariationFromKey($experimentKey, $variationKey),
                $impressionEvent
            )
        );
    }

    /**
     * Buckets visitor and sends impression event to Optimizely.
     *
     * @param $experimentKey string Key identifying the experiment.
     * @param $userId string ID for user.
     * @param $attributes array Attributes of the user.
     *
     * @return null|string Representing the variation key.
     */
    public function activate($experimentKey, $userId, $attributes = null)
    {
        $config = $this->getConfig();
        if ($config === null) {
            $this->_logger->log(Logger::ERROR, sprintf(Errors::INVALID_DATAFILE, __FUNCTION__));
            return null;
        }

        if (!$this->validateInputs(
            [
                self::EXPERIMENT_KEY =>$experimentKey,
                self::USER_ID => $userId
            ]
        )
        ) {
            return null;
        }

        $variationKey = $this->getVariation($experimentKey, $userId, $attributes);
        if (is_null($variationKey)) {
            $this->_logger->log(Logger::INFO, sprintf('Not activating user "%s".', $userId));
            return null;
        }

        $this->sendImpressionEvent($config, $experimentKey, $variationKey, $userId, $attributes);

        return $variationKey;
    }

    /**
     * Gets OptimizelyConfig object for the current ProjectConfig.
     *
     * @return OptimizelyConfig Representing current ProjectConfig.
     */
    public function getOptimizelyConfig()
    {
        $config = $this->getConfig();
        if ($config === null) {
            $this->_logger->log(Logger::ERROR, sprintf(Errors::INVALID_DATAFILE, __FUNCTION__));
            return null;
        }

        $optConfigService = new OptimizelyConfigService($config);
        
        return $optConfigService->getConfig();
    }

    /**
     * Send conversion event to Optimizely.
     *
     * @param $eventKey string Event key representing the event which needs to be recorded.
     * @param $userId string ID for user.
     * @param $attributes array Attributes of the user.
     * @param $eventTags array Hash representing metadata associated with the event.
     */
    public function track($eventKey, $userId, $attributes = null, $eventTags = null)
    {
        $config = $this->getConfig();
        if ($config === null) {
            $this->_logger->log(Logger::ERROR, sprintf(Errors::INVALID_DATAFILE, __FUNCTION__));
            return;
        }

        if (!$this->validateInputs(
            [
                self::EVENT_KEY =>$eventKey,
                self::USER_ID => $userId
            ]
        )
        ) {
            return null;
        }

        if (!$this->validateUserInputs($attributes, $eventTags)) {
            return;
        }

        $event = $config->getEvent($eventKey);

        if (is_null($event->getKey())) {
            $this->_logger->log(Logger::INFO, sprintf('Not tracking user "%s" for event "%s".', $userId, $eventKey));
            return;
        }

        $conversionEvent = $this->_eventBuilder
            ->createConversionEvent(
                $config,
                $eventKey,
                $userId,
                $attributes,
                $eventTags
            );

        $this->_logger->log(Logger::INFO, sprintf('Tracking event "%s" for user "%s".', $eventKey, $userId));
        $this->_logger->log(
            Logger::DEBUG,
            sprintf(
                'Dispatching conversion event to URL %s with params %s.',
                $conversionEvent->getUrl(),
                json_encode($conversionEvent->getParams())
            )
        );

        try {
            $this->_eventDispatcher->dispatchEvent($conversionEvent);
        } catch (Throwable $exception) {
            $this->_logger->log(
                Logger::ERROR,
                sprintf(
                    'Unable to dispatch conversion event. Error %s',
                    $exception->getMessage()
                )
            );
        } catch (Exception $exception) {
            $this->_logger->log(
                Logger::ERROR,
                sprintf(
                    'Unable to dispatch conversion event. Error %s',
                    $exception->getMessage()
                )
            );
        }

        $this->notificationCenter->sendNotifications(
            NotificationType::TRACK,
            array(
                $eventKey,
                $userId,
                $attributes,
                $eventTags,
                $conversionEvent
            )
        );
    }

    /**
     * Get variation where user will be bucketed.
     *
     * @param $experimentKey string Key identifying the experiment.
     * @param $userId string ID for user.
     * @param $attributes array Attributes of the user.
     *
     * @return null|string Representing the variation key.
     */
    public function getVariation($experimentKey, $userId, $attributes = null)
    {
        // TODO: Config should be passed as param when this is called from activate but
        // since PHP is single-threaded we can leave this for now.
        $config = $this->getConfig();
        if ($config === null) {
            $this->_logger->log(Logger::ERROR, sprintf(Errors::INVALID_DATAFILE, __FUNCTION__));
            return null;
        }

        if (!$this->validateInputs(
            [
                self::EXPERIMENT_KEY =>$experimentKey,
                self::USER_ID => $userId
            ]
        )
        ) {
            return null;
        }

        $experiment = $config->getExperimentFromKey($experimentKey);

        if (is_null($experiment->getKey())) {
            return null;
        }

        if (!$this->validateUserInputs($attributes)) {
            return null;
        }

        $variation = $this->_decisionService->getVariation($config, $experiment, $userId, $attributes);
        $variationKey = ($variation === null) ? null : $variation->getKey();

        if ($config->isFeatureExperiment($experiment->getId())) {
            $decisionNotificationType = DecisionNotificationTypes::FEATURE_TEST;
        } else {
            $decisionNotificationType = DecisionNotificationTypes::AB_TEST;
        }

        $attributes = $attributes ?: [];
        $this->notificationCenter->sendNotifications(
            NotificationType::DECISION,
            array(
                $decisionNotificationType,
                $userId,
                $attributes,
                (object) array(
                    'experimentKey'=> $experiment->getKey(),
                    'variationKey'=> $variationKey
                )
            )
        );

        return $variationKey;
    }

    /**
     * Force a user into a variation for a given experiment.
     *
     * @param $experimentKey string Key identifying the experiment.
     * @param $userId string The user ID to be used for bucketing.
     * @param $variationKey string The variation key specifies the variation which the user
     * will be forced into. If null, then clear the existing experiment-to-variation mapping.
     *
     * @return boolean A boolean value that indicates if the set completed successfully.
     */
    public function setForcedVariation($experimentKey, $userId, $variationKey)
    {
        $config = $this->getConfig();
        if ($config === null) {
            $this->_logger->log(Logger::ERROR, sprintf(Errors::INVALID_DATAFILE, __FUNCTION__));
            return false;
        }

        if (!$this->validateInputs(
            [
                self::EXPERIMENT_KEY =>$experimentKey,
                self::USER_ID => $userId
            ]
        )) {
            return false;
        }
        return $this->_decisionService->setForcedVariation($config, $experimentKey, $userId, $variationKey);
    }

    /**
     * Gets the forced variation for a given user and experiment.
     *
     * @param $experimentKey string Key identifying the experiment.
     * @param $userId string The user ID to be used for bucketing.
     *
     * @return string|null The forced variation key.
     */
    public function getForcedVariation($experimentKey, $userId)
    {
        $config = $this->getConfig();
        if ($config === null) {
            $this->_logger->log(Logger::ERROR, sprintf(Errors::INVALID_DATAFILE, __FUNCTION__));
            return null;
        }

        if (!$this->validateInputs(
            [
                self::EXPERIMENT_KEY =>$experimentKey,
                self::USER_ID => $userId
            ]
        )) {
            return null;
        }

        $forcedVariation = $this->_decisionService->getForcedVariation($config, $experimentKey, $userId);
        if (isset($forcedVariation)) {
            return $forcedVariation->getKey();
        } else {
            return null;
        }
    }

    /**
     * Determine whether a feature is enabled.
     * Sends an impression event if the user is bucketed into an experiment using the feature.
     *
     * @param string Feature flag key
     * @param string User ID
     * @param array Associative array of user attributes
     *
     * @return boolean
     */
    public function isFeatureEnabled($featureFlagKey, $userId, $attributes = null)
    {
        $config = $this->getConfig();
        if ($config === null) {
            $this->_logger->log(Logger::ERROR, sprintf(Errors::INVALID_DATAFILE, __FUNCTION__));
            return false;
        }

        if (!$this->validateInputs(
            [
                self::FEATURE_FLAG_KEY =>$featureFlagKey,
                self::USER_ID => $userId
            ]
        )
        ) {
            return false;
        }

        $featureFlag = $config->getFeatureFlagFromKey($featureFlagKey);
        if ($featureFlag && (!$featureFlag->getId())) {
            // Error logged in DatafileProjectConfig - getFeatureFlagFromKey
            return false;
        }

        //validate feature flag
        if (!Validator::isFeatureFlagValid($config, $featureFlag)) {
            return false;
        }

        $featureEnabled = false;
        $decision = $this->_decisionService->getVariationForFeature($config, $featureFlag, $userId, $attributes);
        $variation = $decision->getVariation();
        if ($variation) {
            $experiment = $decision->getExperiment();
            $featureEnabled = $variation->getFeatureEnabled();
            if ($decision->getSource() == FeatureDecision::DECISION_SOURCE_FEATURE_TEST) {
                $experimentKey = $experiment->getKey();
                $variationKey = $variation->getKey();
                $sourceInfo = (object) array(
                    'experimentKey'=> $experimentKey,
                    'variationKey'=> $variationKey
                );

                $this->sendImpressionEvent($config, $experimentKey, $variationKey, $userId, $attributes);
            } else {
                $this->_logger->log(Logger::INFO, "The user '{$userId}' is not being experimented on Feature Flag '{$featureFlagKey}'.");
            }
        }

        $attributes = is_null($attributes) ? [] : $attributes;
        $this->notificationCenter->sendNotifications(
            NotificationType::DECISION,
            array(
                DecisionNotificationTypes::FEATURE,
                $userId,
                $attributes,
                (object) array(
                    'featureKey'=>$featureFlagKey,
                    'featureEnabled'=> $featureEnabled,
                    'source'=> $decision->getSource(),
                    'sourceInfo'=> isset($sourceInfo) ? $sourceInfo : (object) array()
                )
            )
        );

        if ($featureEnabled == true) {
            $this->_logger->log(Logger::INFO, "Feature Flag '{$featureFlagKey}' is enabled for user '{$userId}'.");
            return $featureEnabled;
        }

        $this->_logger->log(Logger::INFO, "Feature Flag '{$featureFlagKey}' is not enabled for user '{$userId}'.");
        return false;
    }

    /**
     * Get keys of all feature flags which are enabled for the user
     *
     * @param  string User ID
     * @param  array Associative array of user attributes
     * @return array List of feature flag keys
     */
    public function getEnabledFeatures($userId, $attributes = null)
    {
        $enabledFeatureKeys = [];

        $config = $this->getConfig();
        if ($config === null) {
            $this->_logger->log(Logger::ERROR, sprintf(Errors::INVALID_DATAFILE, __FUNCTION__));
            return $enabledFeatureKeys;
        }

        if (!$this->validateInputs(
            [
                self::USER_ID => $userId
            ]
        )
        ) {
            return $enabledFeatureKeys;
        }

        $featureFlags = $config->getFeatureFlags();
        foreach ($featureFlags as $feature) {
            $featureKey = $feature->getKey();
            if ($this->isFeatureEnabled($featureKey, $userId, $attributes) === true) {
                $enabledFeatureKeys[] = $featureKey;
            }
        }

        return $enabledFeatureKeys;
    }

    /**
     * Get the string value of the specified variable in the feature flag.
     *
     * @param string Feature flag key
     * @param string Variable key
     * @param string User ID
     * @param array  Associative array of user attributes
     * @param string Variable type
     *
     * @return string Feature variable value / null
     */
    public function getFeatureVariableValueForType(
        $featureFlagKey,
        $variableKey,
        $userId,
        $attributes = null,
        $variableType = null
    ) {
        $config = $this->getConfig();
        if ($config === null) {
            $this->_logger->log(Logger::ERROR, sprintf(Errors::INVALID_DATAFILE, FeatureVariable::getFeatureVariableMethodName($variableType)));
            return null;
        }

        if (!$this->validateInputs(
            [
                self::FEATURE_FLAG_KEY => $featureFlagKey,
                self::VARIABLE_KEY => $variableKey,
                self::USER_ID => $userId
            ]
        )
        ) {
            return null;
        }

        $featureFlag = $config->getFeatureFlagFromKey($featureFlagKey);
        if ($featureFlag && (!$featureFlag->getId())) {
            // Error logged in DatafileProjectConfig - getFeatureFlagFromKey
            return null;
        }

        $variable = $config->getFeatureVariableFromKey($featureFlagKey, $variableKey);
        if (!$variable) {
            // Error message logged in ProjectConfigInterface- getFeatureVariableFromKey
            return null;
        }

        if ($variableType != $variable->getType()) {
            $this->_logger->log(
                Logger::ERROR,
                "Variable is of type '{$variable->getType()}', but you requested it as type '{$variableType}'."
            );
            return null;
        }

        $featureEnabled = false;
        $decision = $this->_decisionService->getVariationForFeature($config, $featureFlag, $userId, $attributes);
        $variableValue = $variable->getDefaultValue();

        if ($decision->getVariation() === null) {
            $this->_logger->log(
                Logger::INFO,
                "User '{$userId}'is not in any variation, ".
                "returning default value '{$variableValue}'."
            );
        } else {
            $experiment = $decision->getExperiment();
            $variation = $decision->getVariation();
            $featureEnabled = $variation->getFeatureEnabled();

            if ($decision->getSource() == FeatureDecision::DECISION_SOURCE_FEATURE_TEST) {
                $sourceInfo = (object) array(
                    'experimentKey'=> $experiment->getKey(),
                    'variationKey'=> $variation->getKey()
                );
            }

            if ($featureEnabled) {
                $variableUsage = $variation->getVariableUsageById($variable->getId());
                if ($variableUsage) {
                    $variableValue = $variableUsage->getValue();
                    $this->_logger->log(
                        Logger::INFO,
                        "Returning variable value '{$variableValue}' for variation '{$variation->getKey()}' ".
                        "of feature flag '{$featureFlagKey}'"
                    );
                } else {
                    $this->_logger->log(
                        Logger::INFO,
                        "Variable '{$variableKey}' is not used in variation '{$variation->getKey()}', ".
                        "returning default value '{$variableValue}'."
                    );
                }
            } else {
                $this->_logger->log(
                    Logger::INFO,
                    "Feature '{$featureFlagKey}' for variation '{$variation->getKey()}' is not enabled, ".
                    "returning default value '{$variableValue}'."
                );
            }
        }

        if (!is_null($variableValue)) {
            $variableValue = VariableTypeUtils::castStringToType($variableValue, $variableType, $this->_logger);
        }

        $attributes = $attributes ?: [];
        $this->notificationCenter->sendNotifications(
            NotificationType::DECISION,
            array(
                DecisionNotificationTypes::FEATURE_VARIABLE,
                $userId,
                $attributes,
                (object) array(
                    'featureKey'=>$featureFlagKey,
                    'featureEnabled'=> $featureEnabled,
                    'variableKey'=> $variableKey,
                    'variableType'=> $variableType,
                    'variableValue'=> $variableValue,
                    'source'=> $decision->getSource(),
                    'sourceInfo'=> isset($sourceInfo) ? $sourceInfo : (object) array()
                )
            )
        );

        return $variableValue;
    }

    /**
     * Get the Boolean value of the specified variable in the feature flag.
     *
     * @param string Feature flag key
     * @param string Variable key
     * @param string User ID
     * @param array  Associative array of user attributes
     *
     * @return string boolean variable value / null
     */
    public function getFeatureVariableBoolean($featureFlagKey, $variableKey, $userId, $attributes = null)
    {
        return $this->getFeatureVariableValueForType(
            $featureFlagKey,
            $variableKey,
            $userId,
            $attributes,
            FeatureVariable::BOOLEAN_TYPE
        );
    }

    /**
     * Get the Integer value of the specified variable in the feature flag.
     *
     * @param string Feature flag key
     * @param string Variable key
     * @param string User ID
     * @param array  Associative array of user attributes
     *
     * @return string integer variable value / null
     */
    public function getFeatureVariableInteger($featureFlagKey, $variableKey, $userId, $attributes = null)
    {
        return $this->getFeatureVariableValueForType(
            $featureFlagKey,
            $variableKey,
            $userId,
            $attributes,
            FeatureVariable::INTEGER_TYPE
        );
    }

    /**
     * Get the Double value of the specified variable in the feature flag.
     *
     * @param string Feature flag key
     * @param string Variable key
     * @param string User ID
     * @param array  Associative array of user attributes
     *
     * @return string double variable value / null
     */
    public function getFeatureVariableDouble($featureFlagKey, $variableKey, $userId, $attributes = null)
    {
        return $this->getFeatureVariableValueForType(
            $featureFlagKey,
            $variableKey,
            $userId,
            $attributes,
            FeatureVariable::DOUBLE_TYPE
        );
    }

    /**
     * Get the String value of the specified variable in the feature flag.
     *
     * @param string Feature flag key
     * @param string Variable key
     * @param string User ID
     * @param array  Associative array of user attributes
     *
     * @return string variable value / null
     */
    public function getFeatureVariableString($featureFlagKey, $variableKey, $userId, $attributes = null)
    {
        return $this->getFeatureVariableValueForType(
            $featureFlagKey,
            $variableKey,
            $userId,
            $attributes,
            FeatureVariable::STRING_TYPE
        );
    }

    /**
     * Determine if the instance of the Optimizely client is valid.
     * An instance can be deemed invalid if it was not initialized
     * properly due to an invalid datafile being passed in.
     *
     * @return True if the Optimizely instance is valid.
     *         False if the Optimizely instance is not valid.
     */
    public function isValid()
    {
        return $this->getConfig() !== null;
    }

    /**
    * Calls Validator::validateNonEmptyString for each value in array
    * Logs for each invalid value
    *
    * @param array values to validate
    * @param logger
    *
    * @return bool True if all of the values are valid, False otherwise
    */
    protected function validateInputs(array $values, $logLevel = Logger::ERROR)
    {
        $isValid = true;
        if (array_key_exists(self::USER_ID, $values)) {
            // Empty str is a valid user ID
            if (!is_string($values[self::USER_ID])) {
                $this->_logger->log(Logger::ERROR, sprintf(Errors::INVALID_FORMAT, self::USER_ID));
                $isValid = false;
            }
            unset($values[self::USER_ID]);
        }

        foreach ($values as $key => $value) {
            if (!Validator::validateNonEmptyString($value)) {
                $isValid = false;
                $message = sprintf(Errors::INVALID_FORMAT, $key);
                $this->_logger->log($logLevel, $message);
            }
        }

        return $isValid;
    }
}
