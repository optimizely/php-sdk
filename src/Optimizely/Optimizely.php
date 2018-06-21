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

use Exception;
use Optimizely\Exceptions\InvalidAttributeException;
use Optimizely\Exceptions\InvalidEventTagException;
use Throwable;
use Monolog\Logger;
use Optimizely\DecisionService\DecisionService;
use Optimizely\DecisionService\FeatureDecision;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\FeatureVariable;
use Optimizely\ErrorHandler\ErrorHandlerInterface;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Event\Builder\EventBuilder;
use Optimizely\Event\Dispatcher\DefaultEventDispatcher;
use Optimizely\Event\Dispatcher\EventDispatcherInterface;
use Optimizely\Logger\DefaultLogger;
use Optimizely\Logger\LoggerInterface;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Notification\NotificationCenter;
use Optimizely\Notification\NotificationType;
use Optimizely\UserProfile\UserProfileServiceInterface;
use Optimizely\Utils\Validator;
use Optimizely\Utils\VariableTypeUtils;

/**
 * Class Optimizely
 *
 * @package Optimizely
 */
class Optimizely
{
    /**
     * @var ProjectConfig
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
     */
    public function __construct(
        $datafile,
        EventDispatcherInterface $eventDispatcher = null,
        LoggerInterface $logger = null,
        ErrorHandlerInterface $errorHandler = null,
        $skipJsonValidation = false,
        UserProfileServiceInterface $userProfileService = null
    ) {
        $this->_isValid = true;
        $this->_eventDispatcher = $eventDispatcher ?: new DefaultEventDispatcher();
        $this->_logger = $logger ?: new NoOpLogger();
        $this->_errorHandler = $errorHandler ?: new NoOpErrorHandler();

        if (!$this->validateDatafile($datafile, $skipJsonValidation)) {
            $this->_isValid = false;
            $defaultLogger = new DefaultLogger();

            $defaultLogger->log(Logger::ERROR, 'Provided "datafile" has invalid schema.');
            $this->_logger->log(Logger::ERROR, 'Provided "datafile" has invalid schema.');
            return;
        }

        try {
            $this->_config = new ProjectConfig($datafile, $this->_logger, $this->_errorHandler);
        } catch (Throwable $exception) {
            $this->_isValid = false;
            $this->_logger = new DefaultLogger();
            $this->_logger->log(Logger::ERROR, 'Provided "datafile" is in an invalid format.');
            return;
        } catch (Exception $exception) {
            $this->_isValid = false;
            $this->_logger = new DefaultLogger();
            $this->_logger->log(Logger::ERROR, 'Provided "datafile" is in an invalid format.');
            return;
        }

        $this->_eventBuilder = new EventBuilder($this->_logger);
        $this->_decisionService = new DecisionService($this->_logger, $this->_config, $userProfileService);
        $this->notificationCenter = new NotificationCenter($this->_logger, $this->_errorHandler);
    }

    /**
     * @param $datafile string JSON string representing the project.
     * @param $skipJsonValidation boolean representing whether JSON schema validation needs to be performed.
     *
     * @return boolean Representing whether the provided datafile is valid or not.
     */
    private function validateDatafile($datafile, $skipJsonValidation)
    {
        if (!$skipJsonValidation && !Validator::validateJsonSchema($datafile)) {
            return false;
        }

        return true;
    }

    /**
     * Helper function to validate user inputs into the API methods.
     *
     * @param $userId string ID for user.
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
     * Get the experiments that we should be tracking for the given event. A valid experiment
     * is one that is in "Running" state and into which the user has been bucketed.
     *
     * @param $event string Event key representing the event which needs to be recorded.
     * @param $user string ID for user.
     * @param $attributes array Attributes of the user.
     *
     * @return Array Of objects where each object contains the ID of the experiment to track and the ID of the variation the user is bucketed into.
     */
    private function getValidExperimentsForEvent($event, $userId, $attributes = null)
    {
        $validExperiments = [];
        foreach ($event->getExperimentIds() as $experimentId) {
            $experiment = $this->_config->getExperimentFromId($experimentId);
            $experimentKey = $experiment->getKey();
            $variationKey = $this->getVariation($experimentKey, $userId, $attributes);

            if (is_null($variationKey)) {
                $this->_logger->log(
                    Logger::INFO,
                    sprintf(
                        'Not tracking user "%s" for experiment "%s".',
                        $userId,
                        $experimentKey
                    )
                );
                continue;
            }

            $variation = $this->_config->getVariationFromKey($experimentKey, $variationKey);
            $validExperiments[$experimentId] = $variation->getId();
        }

        return $validExperiments;
    }

    /**
     * @param  string Experiment key
     * @param  string Variation key
     * @param  string User ID
     * @param  array Associative array of user attributes
     */
    protected function sendImpressionEvent($experimentKey, $variationKey, $userId, $attributes)
    {
        $impressionEvent = $this->_eventBuilder
            ->createImpressionEvent($this->_config, $experimentKey, $variationKey, $userId, $attributes);
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
                $this->_config->getExperimentFromKey($experimentKey),
                $userId,
                $attributes,
                $this->_config->getVariationFromKey($experimentKey, $variationKey),
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
        if (!$this->_isValid) {
            $this->_logger->log(Logger::ERROR, 'Datafile has invalid format. Failing "activate".');
            return null;
        }

        $variationKey = $this->getVariation($experimentKey, $userId, $attributes);
        if (is_null($variationKey)) {
            $this->_logger->log(Logger::INFO, sprintf('Not activating user "%s".', $userId));
            return null;
        }

        $this->sendImpressionEvent($experimentKey, $variationKey, $userId, $attributes);

        return $variationKey;
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
        if (!$this->_isValid) {
            $this->_logger->log(Logger::ERROR, 'Datafile has invalid format. Failing "track".');
            return;
        }

        if (!$this->validateUserInputs($attributes, $eventTags)) {
            return;
        }

        $event = $this->_config->getEvent($eventKey);

        if (is_null($event->getKey())) {
            $this->_logger->log(Logger::ERROR, sprintf('Not tracking user "%s" for event "%s".', $userId, $eventKey));
            return;
        }

        // Filter out experiments that are not running or when user(s) do not meet conditions.
        $experimentVariationMap = $this->getValidExperimentsForEvent($event, $userId, $attributes);
        if (!empty($experimentVariationMap)) {
            $conversionEvent = $this->_eventBuilder
                ->createConversionEvent(
                    $this->_config,
                    $eventKey,
                    $experimentVariationMap,
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
        } else {
            $this->_logger->log(
                Logger::INFO,
                sprintf('There are no valid experiments for event "%s" to track.', $eventKey)
            );
        }
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
        if (!$this->_isValid) {
            $this->_logger->log(Logger::ERROR, 'Datafile has invalid format. Failing "getVariation".');
            return null;
        }

        $experiment = $this->_config->getExperimentFromKey($experimentKey);

        if (is_null($experiment->getKey())) {
            return null;
        }

        if (!$this->validateUserInputs($attributes)) {
            return null;
        }

        $variation = $this->_decisionService->getVariation($experiment, $userId, $attributes);
        if (is_null($variation)) {
            return null;
        }

        return $variation->getKey();
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
        return $this->_config->setForcedVariation($experimentKey, $userId, $variationKey);
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
        $forcedVariation = $this->_config->getForcedVariation($experimentKey, $userId);
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
        if (!$this->_isValid) {
            $this->_logger->log(Logger::ERROR, "Datafile has invalid format. Failing '".__FUNCTION__."'.");
            return false;
        }

        if (!$featureFlagKey) {
            $this->_logger->log(Logger::ERROR, "Feature Flag key cannot be empty.");
            return false;
        }

        if (!$userId) {
            $this->_logger->log(Logger::ERROR, "User ID cannot be empty.");
            return false;
        }

        $featureFlag = $this->_config->getFeatureFlagFromKey($featureFlagKey);
        if ($featureFlag && (!$featureFlag->getId())) {
            // Error logged in ProjectConfig - getFeatureFlagFromKey
            return false;
        }

        //validate feature flag
        if (!Validator::isFeatureFlagValid($this->_config, $featureFlag)) {
            return false;
        }

        $decision = $this->_decisionService->getVariationForFeature($featureFlag, $userId, $attributes);
        if (!$decision) {
            $this->_logger->log(Logger::INFO, "Feature Flag '{$featureFlagKey}' is not enabled for user '{$userId}'.");
            return false;
        }

        $experiment = $decision->getExperiment();
        $variation = $decision->getVariation();

        if ($decision->getSource() == FeatureDecision::DECISION_SOURCE_EXPERIMENT) {
            $this->sendImpressionEvent($experiment->getKey(), $variation->getKey(), $userId, $attributes);
        } else {
            $this->_logger->log(Logger::INFO, "The user '{$userId}' is not being experimented on Feature Flag '{$featureFlagKey}'.");
        }

        if ($variation->getFeatureEnabled()) {
            $this->_logger->log(Logger::INFO, "Feature Flag '{$featureFlagKey}' is enabled for user '{$userId}'.");
            return true;
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

        if (!$this->_isValid) {
            $this->_logger->log(Logger::ERROR, "Datafile has invalid format. Failing '".__FUNCTION__."'.");
            return $enabledFeatureKeys;
        }

        $featureFlags = $this->_config->getFeatureFlags();
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
        if (!$featureFlagKey) {
            $this->_logger->log(Logger::ERROR, "Feature Flag key cannot be empty.");
            return null;
        }

        if (!$variableKey) {
            $this->_logger->log(Logger::ERROR, "Variable key cannot be empty.");
            return null;
        }

        if (!$userId) {
            $this->_logger->log(Logger::ERROR, "User ID cannot be empty.");
            return null;
        }

        $featureFlag = $this->_config->getFeatureFlagFromKey($featureFlagKey);
        if ($featureFlag && (!$featureFlag->getId())) {
            // Error logged in ProjectConfig - getFeatureFlagFromKey
            return null;
        }

        $variable = $this->_config->getFeatureVariableFromKey($featureFlagKey, $variableKey);
        if (!$variable) {
            // Error message logged in ProjectConfig- getFeatureVariableFromKey
            return null;
        }

        if ($variableType != $variable->getType()) {
            $this->_logger->log(
                Logger::ERROR,
                "Variable is of type '{$variable->getType()}', but you requested it as type '{$variableType}'."
            );
            return null;
        }

        $decision = $this->_decisionService->getVariationForFeature($featureFlag, $userId, $attributes);
        $variableValue = $variable->getDefaultValue();

        if (!$decision) {
            $this->_logger->log(
                Logger::INFO,
                "User '{$userId}'is not in any variation, ".
                "returning default value '{$variableValue}'."
            );
        } else {
            $variation = $decision->getVariation();
            $variable_usage = $variation->getVariableUsageById($variable->getId());
            if ($variable_usage) {
                $variableValue = $variable_usage->getValue();
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
        }

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
        $variableValue = $this->getFeatureVariableValueForType(
            $featureFlagKey,
            $variableKey,
            $userId,
            $attributes,
            FeatureVariable::BOOLEAN_TYPE
        );

        if (!is_null($variableValue)) {
            return VariableTypeUtils::castStringToType($variableValue, FeatureVariable::BOOLEAN_TYPE, $this->_logger);
        }

        return $variableValue;
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
        $variableValue = $this->getFeatureVariableValueForType(
            $featureFlagKey,
            $variableKey,
            $userId,
            $attributes,
            FeatureVariable::INTEGER_TYPE
        );

        if (!is_null($variableValue)) {
            return VariableTypeUtils::castStringToType($variableValue, FeatureVariable::INTEGER_TYPE, $this->_logger);
        }

        return $variableValue;
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
        $variableValue = $this->getFeatureVariableValueForType(
            $featureFlagKey,
            $variableKey,
            $userId,
            $attributes,
            FeatureVariable::DOUBLE_TYPE
        );

        if (!is_null($variableValue)) {
            return VariableTypeUtils::castStringToType($variableValue, FeatureVariable::DOUBLE_TYPE, $this->_logger);
        }

        return $variableValue;
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
        $variableValue = $this->getFeatureVariableValueForType(
            $featureFlagKey,
            $variableKey,
            $userId,
            $attributes,
            FeatureVariable::STRING_TYPE
        );

        return $variableValue;
    }
}
