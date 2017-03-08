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

use Exception;
use Optimizely\Exceptions\InvalidAttributeException;
use Throwable;
use Monolog\Logger;
use Optimizely\Entity\Experiment;
use Optimizely\ErrorHandler\ErrorHandlerInterface;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Event\Builder\EventBuilder;
use Optimizely\Event\Dispatcher\DefaultEventDispatcher;
use Optimizely\Event\Dispatcher\EventDispatcherInterface;
use Optimizely\Logger\LoggerInterface;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Utils\Validator;

/**
 * Class Optimizely
 *
 * @package Optimizely
 */
class Optimizely
{
    /**
     * @var EventDispatcherInterface
     */
    private $_eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var ErrorHandlerInterface 
     */
    private $_errorHandler;

    /**
     * @var ProjectConfig
     */
    private $_config;

    /**
     * @var Bucketer
     */
    private $_bucketer;

    /**
     * @var EventBuilder
     */
    private $_eventBuilder;

    /**
     * @var boolean Denotes whether Optimizely object is valid or not.
     */
    private $_isValid;

    /**
     * Optimizely constructor for managing Full Stack PHP projects.
     *
     * @param $datafile string JSON string representing the project.
     * @param $eventDispatcher EventDispatcherInterface
     * @param $logger LoggerInterface
     * @param $errorHandler ErrorHandlerInterface
     * @param $skipJsonValidation boolean representing whether JSON schema validation needs to be performed.
     */
    public function __construct(
        $datafile,
        EventDispatcherInterface $eventDispatcher = null,
        LoggerInterface $logger = null,
        ErrorHandlerInterface $errorHandler = null,
        $skipJsonValidation = false
    ) {
        $this->_isValid = true;
        $this->_eventDispatcher = $eventDispatcher ?: new DefaultEventDispatcher();
        $this->_logger = $logger ?: new NoOpLogger();
        $this->_errorHandler = $errorHandler ?: new NoOpErrorHandler();

        if (!$this->validateInputs($datafile, $skipJsonValidation)) {
            $this->_isValid = false;
            $this->_logger->log(Logger::ERROR, 'Provided "datafile" has invalid schema.');
            return;
        }

        try {
          $this->_config = new ProjectConfig($datafile, $this->_logger, $this->_errorHandler);
        }
        catch (Throwable $exception) {
            $this->_isValid = false;
            $this->_logger->log(Logger::ERROR, 'Provided "datafile" is in an invalid format.');
            return;
        }
        catch (Exception $exception) {
            $this->_isValid = false;
            $this->_logger->log(Logger::ERROR, 'Provided "datafile" is in an invalid format.');
            return;
        }

        $this->_bucketer = new Bucketer($this->_logger);
        $this->_eventBuilder = new EventBuilder($this->_bucketer);
    }

    /**
     * @param $datafile string JSON string representing the project.
     * @param $skipJsonValidation boolean representing whether JSON schema validation needs to be performed.
     *
     * @return boolean Representing whether all provided inputs are valid or not.
     */
    private function validateInputs($datafile, $skipJsonValidation)
    {
        if (!$skipJsonValidation && !Validator::validateJsonSchema($datafile)) {
            return false;
        }

        return true;
    }

    /**
     * Helper function to validate all required conditions before performing activate or track.
     *
     * @param $experiment Experiment Object representing experiment.
     * @param $userId string ID for user.
     * @param $attributes array User attributes.
     * @return boolean Representing whether all conditions are met or not.
     */
    private function validatePreconditions($experiment, $userId, $attributes)
    {
        if (!is_null($attributes) && !Validator::areAttributesValid($attributes)) {
            $this->_logger->log(Logger::ERROR, 'Provided attributes are in an invalid format.');
            $this->_errorHandler->handleError(
                new InvalidAttributeException('Provided attributes are in an invalid format.')
            );
            return false;
        }

        if (!$experiment->isExperimentRunning()) {
            $this->_logger->log(Logger::INFO, sprintf('Experiment "%s" is not running.', $experiment->getKey()));
            return false;
        }

        if ($experiment->isUserInForcedVariation($userId)) {
            return true;
        }

        if (!Validator::isUserInExperiment($this->_config, $experiment, $attributes)) {
            $this->_logger->log(
                Logger::INFO,
                sprintf('User "%s" does not meet conditions to be in experiment "%s".', $userId, $experiment->getKey())
            );
            return false;
        }

        return true;
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

        $experiment = $this->_config->getExperimentFromKey($experimentKey);

        if (is_null($experiment->getKey())) {
            $this->_logger->log(Logger::INFO, sprintf('Not activating user "%s".', $userId));
            return null;
        }

        if (!$this->validatePreconditions($experiment, $userId, $attributes)) {
            $this->_logger->log(Logger::INFO, sprintf('Not activating user "%s".', $userId));
            return null;
        }

        $variation = $this->_bucketer->bucket($this->_config, $experiment, $userId);
        $variationKey = $variation->getKey();

        if (is_null($variationKey)) {
            $this->_logger->log(Logger::INFO, sprintf('Not activating user "%s".', $userId));
            return $variationKey;
        }

        $impressionEvent = $this->_eventBuilder
            ->createImpressionEvent($this->_config, $experiment, $variation->getId(), $userId, $attributes);
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
     * @param $eventValue integer Value associated with event.
     */
    public function track($eventKey, $userId, $attributes = null, $eventValue = null)
    {
        if (!$this->_isValid) {
            $this->_logger->log(Logger::ERROR, 'Datafile has invalid format. Failing "track".');
            return;
        }

        if (!is_null($attributes) && !Validator::areAttributesValid($attributes)) {
            $this->_logger->log(Logger::ERROR, 'Provided attributes are in an invalid format.');
            $this->_errorHandler->handleError(
                new InvalidAttributeException('Provided attributes are in an invalid format.')
            );
            return;
        }

        $event = $this->_config->getEvent($eventKey);

        if (is_null($event->getKey())) {
            $this->_logger->log(Logger::ERROR, sprintf('Not tracking user "%s" for event "%s".', $userId, $eventKey));
            return;
        }

        // Filter out experiments that are not running or when user(s) do not meet conditions.
        $validExperiments = [];
        forEach ($event->getExperimentIds() as $experimentId) {
            $experiment = $this->_config->getExperimentFromId($experimentId);
            if ($this->validatePreconditions($experiment, $userId, $attributes)) {
                array_push($validExperiments, $experiment);
            } else {
                $this->_logger->log(Logger::INFO, sprintf('Not tracking user "%s" for experiment "%s".',
                    $userId, $experiment->getKey()));
            }
        }

        if (!empty($validExperiments)) {
            $conversionEvent = $this->_eventBuilder
                ->createConversionEvent(
                    $this->_config,
                    $eventKey,
                    $validExperiments,
                    $userId,
                    $attributes,
                    $eventValue
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

        if (!$this->validatePreconditions($experiment, $userId, $attributes)) {
            return null;
        }

        $variation = $this->_bucketer->bucket($this->_config, $experiment, $userId);

        return $variation->getKey();
    }
}
