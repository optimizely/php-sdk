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
use Optimizely\Entity\Experiment;
use Optimizely\Logger\DefaultLogger;
use Throwable;
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
    public function __construct($datafile,
                                EventDispatcherInterface $eventDispatcher = null,
                                LoggerInterface $logger = null,
                                ErrorHandlerInterface $errorHandler = null,
                                $skipJsonValidation = false)
    {
        $this->_isValid = true;
        $this->_eventDispatcher = $eventDispatcher ?: new DefaultEventDispatcher();
        $this->_logger = $logger ?: new NoOpLogger();
        $this->_errorHandler = $errorHandler ?: new NoOpErrorHandler();

        if (!$this->validateInputs($datafile, $skipJsonValidation)) {
            $this->_isValid = false;
            $this->_logger = new DefaultLogger();
        }

        try {
          $this->_config = new ProjectConfig($datafile);
        }
        catch (Throwable $exception) {
            $this->_isValid = false;
            $this->_logger = new DefaultLogger();
        }
        catch (Exception $exception) {
            $this->_isValid = false;
            $this->_logger = new DefaultLogger();
        }

        $this->_bucketer = new Bucketer();
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
        //@TODO(ali): Insert attributes validation

        if (!$experiment->isExperimentRunning()) {
            return false;
        }

        if ($experiment->isUserInForcedVariation($userId)) {
            return true;
        }

        if (!Validator::isUserInExperiment($this->_config, $experiment, $attributes)) {
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
        $experiment = $this->_config->getExperimentFromKey($experimentKey);

        if (is_null($experiment->getKey())) {
            return null;
        }

        if (!$this->validatePreconditions($experiment, $userId, $attributes)) {
            return null;
        }

        $variation = $this->_bucketer->bucket($this->_config, $experiment, $userId);
        $variationKey = $variation->getKey();

        if (is_null($variationKey)) {
            return $variationKey;
        }

        $impressionEvent = $this->_eventBuilder
            ->createImpressionEvent($this->_config, $experiment, $variation->getId(), $userId, $attributes);

        $this->_eventDispatcher->dispatchEvent($impressionEvent);

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
        if (!is_null($attributes) && !Validator::areAttributesValid($attributes)) {
            return;
        }

        $event = $this->_config->getEvent($eventKey);

        if (is_null($event->getKey())) {
            return;
        }

        // Filter out experiments that are not running or when user(s) do not meet conditions.
        $validExperiments = [];
        forEach ($event->getExperimentIds() as $experimentId) {
            $experiment = $this->_config->getExperimentFromId($experimentId);
            if ($this->validatePreconditions($experiment, $userId, $attributes)) {
                array_push($validExperiments, $experiment);
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
            $this->_eventDispatcher->dispatchEvent($conversionEvent);
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
