<?php
/**
 * Copyright 2016-2017, Optimizely
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
use Optimizely\Entity\Experiment;
use Optimizely\Logger\DefaultLogger;
use Optimizely\ErrorHandler\ErrorHandlerInterface;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Event\Builder\EventBuilder;
use Optimizely\Event\Dispatcher\DefaultEventDispatcher;
use Optimizely\Event\Dispatcher\EventDispatcherInterface;
use Optimizely\Logger\LoggerInterface;
use Optimizely\Logger\NoOpLogger;
use Optimizely\UserProfile\UserProfileServiceInterface;
use Optimizely\Utils\EventTagUtils;
use Optimizely\Utils\Validator;

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
     * Optimizely constructor for managing Full Stack PHP projects.
     *
     * @param $datafile string JSON string representing the project.
     * @param $eventDispatcher EventDispatcherInterface
     * @param $logger LoggerInterface
     * @param $errorHandler ErrorHandlerInterface
     * @param $skipJsonValidation boolean representing whether JSON schema validation needs to be performed.
     * @param $userProfileService UserProfileServiceInterface
     */
    public function __construct($datafile,
                                EventDispatcherInterface $eventDispatcher = null,
                                LoggerInterface $logger = null,
                                ErrorHandlerInterface $errorHandler = null,
                                $skipJsonValidation = false,
                                UserProfileServiceInterface $userProfileService = null)
    {
        $this->_isValid = true;
        $this->_eventDispatcher = $eventDispatcher ?: new DefaultEventDispatcher();
        $this->_logger = $logger ?: new NoOpLogger();
        $this->_errorHandler = $errorHandler ?: new NoOpErrorHandler();

        if (!$this->validateDatafile($datafile, $skipJsonValidation)) {
            $this->_isValid = false;
            $this->_logger = new DefaultLogger();
            $this->_logger->log(Logger::ERROR, 'Provided "datafile" has invalid schema.');
            return;
        }

        try {
          $this->_config = new ProjectConfig($datafile, $this->_logger, $this->_errorHandler);
        }
        catch (Throwable $exception) {
            $this->_isValid = false;
            $this->_logger = new DefaultLogger();
            $this->_logger->log(Logger::ERROR, 'Provided "datafile" is in an invalid format.');
            return;
        }
        catch (Exception $exception) {
            $this->_isValid = false;
            $this->_logger = new DefaultLogger();
            $this->_logger->log(Logger::ERROR, 'Provided "datafile" is in an invalid format.');
            return;
        }

        $this->_eventBuilder = new EventBuilder();
        $this->_decisionService = new DecisionService($this->_logger, $this->_config, $userProfileService);
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
     * @param  $userId string ID for user.
     * @param  $eventTags array Hash representing metadata associated with an event.
     *
     * @return boolean Representing whether all user inputs are valid.
     */
    private function validateUserInputs($attributes, $eventTags = null) {
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
            }
        }

        return true;
    }

    /**
     * Get the experiments that we should be tracking for the given event. A valid experiment
     * is one that is in "Running" state and into which the user has been bucketed.
     *
     * @param  $event string Event key representing the event which needs to be recorded.
     * @param  $user string ID for user.
     * @param  $attributes array Attributes of the user.
     *
     * @return Array Of objects where each object contains the ID of the experiment to track and the ID of the variation the user is bucketed into.
     */
    private function getValidExperimentsForEvent($event, $userId, $attributes = null) {
        $validExperiments = [];
        forEach ($event->getExperimentIds() as $experimentId) {
            $experiment = $this->_config->getExperimentFromId($experimentId);
            $experimentKey = $experiment->getKey();
            $variationKey = $this->getVariation($experimentKey, $userId, $attributes);

            if (is_null($variationKey)) {
                $this->_logger->log(Logger::INFO, sprintf('Not tracking user "%s" for experiment "%s".',
                    $userId, $experimentKey));
                continue;
            }

            $variation = $this->_config->getVariationFromKey($experimentKey, $variationKey);
            $validExperiments[$experimentId] = $variation->getId();
        }

        return $validExperiments;
    }

    /**
     * Buckets visitor and sends impression event to Optimizely.
     *
     * @param $experimentKey string Key identifying the experiment.
     * @param $userId string ID for user.
     * @param $attributes array Attributes of the user.
     *
     * @return null|string Representing variation.
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
            return $variationKey;
        }

        $impressionEvent = $this->_eventBuilder
            ->createImpressionEvent($this->_config, $experimentKey, $variationKey, $userId, $attributes);
        $this->_logger->log(Logger::INFO, sprintf('Activating user "%s" in experiment "%s".', $userId, $experimentKey));
        $this->_logger->log(
            Logger::DEBUG,
            sprintf('Dispatching impression event to URL %s with params %s.',
                $impressionEvent->getUrl(), http_build_query($impressionEvent->getParams())
            )
        );

        try {
            $this->_eventDispatcher->dispatchEvent($impressionEvent);
        }
        catch (Throwable $exception) {
            $this->_logger->log(Logger::ERROR, sprintf(
                'Unable to dispatch impression event. Error %s', $exception->getMessage()));
        }
        catch (Exception $exception) {
            $this->_logger->log(Logger::ERROR, sprintf(
                'Unable to dispatch impression event. Error %s', $exception->getMessage()));
        }

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

        if (!is_null($eventTags) && is_numeric($eventTags) && !is_string($eventTags)) {
            $eventTags = array(
                EventTagUtils::REVENUE_EVENT_METRIC_NAME => $eventTags,
            );
            $this->_logger->log(
                Logger::WARNING,
                'Event value is deprecated in track call. Use event tags to pass in revenue value instead.'
            );
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
                sprintf('Dispatching conversion event to URL %s with params %s.',
                    $conversionEvent->getUrl(), http_build_query($conversionEvent->getParams())
                ));

            try {
                $this->_eventDispatcher->dispatchEvent($conversionEvent);
            }
            catch (Throwable $exception) {
                $this->_logger->log(Logger::ERROR, sprintf(
                    'Unable to dispatch conversion event. Error %s', $exception->getMessage()));
            }
            catch (Exception $exception) {
                $this->_logger->log(Logger::ERROR, sprintf(
                    'Unable to dispatch conversion event. Error %s', $exception->getMessage()));
            }

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
     * @return null|string Representing variation.
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
}
