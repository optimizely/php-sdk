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

namespace Optimizely\Event\Builder;
include('Params.php');

use Optimizely\Bucketer;
use Optimizely\Entity\Experiment;
use Optimizely\Event\LogEvent;
use Optimizely\ProjectConfig;

class EventBuilder
{
    /**
     * @const string String denoting SDK type.
     */
    const SDK_TYPE = 'php-sdk';

    /**
     * @const string Version of the Optimizely PHP SDK.
     */
    const SDK_VERSION = '1.0.1';

    /**
     * @var string URL to send impression event to.
     */
    private static $IMPRESSION_ENDPOINT = 'https://logx.optimizely.com/log/decision';

    /**
     * @var string URL to send conversion event to.
     */
    private static $CONVERSION_ENDPOINT = 'https://logx.optimizely.com/log/event';

    /**
     * @var string HTTP method to be used when making call to log endpoint.
     */
    private static $HTTP_VERB = 'POST';

    /**
     * @var array HTTP headers to be set when making call to log endpoint.
     */
    private static $HTTP_HEADERS = [
        'Content-Type' => 'application/json'
    ];

    /**
     * @var array Associative array of parameters to be sent for the event.
     */
    private $_eventParams;

    /**
     * @var Bucketer Providing Optimizely's bucket method.
     */
    private $_bucketer;

    /**
     * EventBuilder constructor.
     *
     * @param $bucketer Bucketer
     */
    public function __construct(Bucketer $bucketer)
    {
        $this->_bucketer = $bucketer;
    }

    /**
     * Helper function to reset event params.
     */
    private function resetParams()
    {
        $this->_eventParams = [];
    }

    /**
     * @return array Params for the event.
     */
    private function getParams()
    {
        return $this->_eventParams;
    }

    /**
     * Helper function to set parameters common to impression and conversion event.
     *
     * @param $config ProjectConfig Configuration for the project.
     * @param $userId string ID of user.
     * @param $attributes array Attributes of the user.
     */
    private function setCommonParams($config, $userId, $attributes)
    {
        $this->_eventParams[PROJECT_ID] = $config->getProjectId();
        $this->_eventParams[ACCOUNT_ID] = $config->getAccountId();
        $this->_eventParams[VISITOR_ID] = $userId;
        $this->_eventParams[CLIENT_ENGINE] = self::SDK_TYPE;
        $this->_eventParams[CLIENT_VERSION] = self::SDK_VERSION;
        $this->_eventParams[USER_FEATURES] = [];
        $this->_eventParams[IS_GLOBAL_HOLDBACK] = false;
        $this->_eventParams[TIME] = time()*1000;
        if (!isset($attributes)) {
            $attributes = [];
        }

        forEach ($attributes as $attributeKey => $attributeValue) {
            if ($attributeValue) {
                $attributeEntity = $config->getAttribute($attributeKey);
                if (!is_null($attributeEntity->getKey())) {
                    array_push($this->_eventParams[USER_FEATURES], [
                        'id' => $attributeEntity->getId(),
                        'name' => $attributeKey,
                        'type' => 'custom',
                        'value' => $attributeValue,
                        'shouldIndex' => true
                    ]);
                }
            }
        }
    }

    /**
     * Helper function to set parameters specific to impression event.
     *
     * @param $experiment Experiment Experiment being activated.
     * @param $variationId string
     */
    private function setImpressionParams(Experiment $experiment, $variationId)
    {
        $this->_eventParams[LAYER_ID] = $experiment->getLayerId();
        $this->_eventParams[DECISION] = [
            EXPERIMENT_ID => $experiment->getId(),
            VARIATION_ID => $variationId,
            IS_LAYER_HOLDBACK => false
        ];
    }

    /**
     * Helper function to set parameters specific to conversion event.
     *
     * @param $config ProjectConfig Configuration for the project.
     * @param $eventKey string Key representing the event.
     * @param $experiments array Experiments for which conversion event needs to be recorded.
     * @param $userId string ID of user.
     * @param $eventTags array Hash representing metadata associated with the event.
     */
    private function setConversionParams($config, $eventKey, $experiments, $userId, $eventTags)
    {
        $this->_eventParams[EVENT_FEATURES] = [];
        $this->_eventParams[EVENT_METRICS] = [];

        if (!is_null($eventTags)) {
            forEach ($eventTags as $eventTagId => $eventTagValue) {
                if (is_null($eventTagValue)) {
                    continue;
                }
                $eventFeature = array(
                    'id' => $eventTagId,
                    'type' => 'custom',
                    'value' => $eventTagValue,
                    'shouldIndex' => false,
                );
                array_push($this->_eventParams[EVENT_FEATURES], $eventFeature);

                if ($eventTagId == 'revenue') {
                    $eventMetric = array(
                        'name' => 'revenue',
                        'value' => $eventTagValue,
                    );
                    array_push($this->_eventParams[EVENT_METRICS], $eventMetric);
                }
            }
        }

        $eventEntity = $config->getEvent($eventKey);
        $this->_eventParams[EVENT_ID] = $eventEntity->getId();
        $this->_eventParams[EVENT_NAME] = $eventKey;

        $this->_eventParams[LAYER_STATES] = [];
        forEach ($experiments as $experiment) {
            $variation = $this->_bucketer->bucket($config, $experiment, $userId);
            if (!is_null($variation->getKey())) {
                array_push($this->_eventParams[LAYER_STATES], [
                    LAYER_ID => $experiment->getLayerId(),
                    ACTION_TRIGGERED => true,
                    DECISION => [
                        EXPERIMENT_ID => $experiment->getId(),
                        VARIATION_ID => $variation->getId(),
                        IS_LAYER_HOLDBACK => false
                    ]
                ]);
            }
        }
    }

    /**
     * Create impression event to be sent to the logging endpoint.
     *
     * @param $config ProjectConfig Configuration for the project.
     * @param $experiment Experiment Experiment being activated.
     * @param $variationId string Variation user
     * @param $userId string ID of user.
     * @param $attributes array Attributes of the user.
     *
     * @return LogEvent Event object to be sent to dispatcher.
     */
    public function createImpressionEvent($config, Experiment $experiment, $variationId, $userId, $attributes)
    {
        $this->resetParams();
        $this->setCommonParams($config, $userId, $attributes);
        $this->setImpressionParams($experiment, $variationId);

        return new LogEvent(self::$IMPRESSION_ENDPOINT, $this->getParams(), self::$HTTP_VERB, self::$HTTP_HEADERS);
    }

    /**
     * Create conversion event to be sent to the logging endpoint.
     *
     * @param $config ProjectConfig Configuration for the project.
     * @param $eventKey string Key representing the event.
     * @param $experiments array Experiments for which conversion event needs to be recorded.
     * @param $userId string ID of user.
     * @param $attributes array Attributes of the user.
     * @param $eventTags array Hash representing metadata associated with the event.
     *
     * @return LogEvent Event object to be sent to dispatcher.
     */
    public function createConversionEvent($config, $eventKey, $experiments, $userId, $attributes, $eventTags)
    {
        $this->resetParams();
        $this->setCommonParams($config, $userId, $attributes);
        $this->setConversionParams($config, $eventKey, $experiments, $userId, $eventTags);

        return new LogEvent(self::$CONVERSION_ENDPOINT, $this->getParams(), self::$HTTP_VERB, self::$HTTP_HEADERS);
    }
}
