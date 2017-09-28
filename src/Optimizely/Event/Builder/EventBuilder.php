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

namespace Optimizely\Event\Builder;
include('Params.php');

use Optimizely\Entity\Experiment;
use Optimizely\Event\LogEvent;
use Optimizely\ProjectConfig;
use Optimizely\Utils\EventTagUtils;
use Optimizely\Utils\GeneratorUtils;

define("RESERVED_ATTRIBUTE_KEY_BUCKETING_ID_EVENT_PARAM_KEY",     "optimizely_bucketing_id");

class EventBuilder
{
    /**
     * @const string String denoting SDK type.
     */
    const SDK_TYPE = 'php-sdk';

    /**
     * @const string Version of the Optimizely PHP SDK.
     */
    const SDK_VERSION = '1.4.0';

    /**
     * @var string URL to send event to.
     */
    private static $ENDPOINT = 'https://logx.optimizely.com/v1/events';

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
     * Helper function to get parameters common to impression and conversion events.
     *
     * @param $config ProjectConfig Configuration for the project.
     * @param $userId string ID of user.
     * @param $attributes array Attributes of the user.
     */
    private function getCommonParams($config, $userId, $attributes)
    {
        $visitor = [
            SNAPSHOTS=> [],
            VISITOR_ID => $userId,
            ATTRIBUTES => []
        ];

        $commonParams = [
            ACCOUNT_ID => $config->getAccountId(),
            PROJECT_ID => $config->getProjectId(),
            VISITORS => [$visitor],
            REVISION => $config->getRevision(),
            CLIENT_ENGINE => self::SDK_TYPE,
            CLIENT_VERSION => self::SDK_VERSION
        ];

        if(is_null($attributes))
            return $commonParams;

        foreach($attributes as $attributeKey => $attributeValue) {
            $feature = [];
            // Do not discard attribute if value is zero or false
            if (!is_null($attributeValue)) {
                // check for reserved attributes
                if (strcmp($attributeKey , RESERVED_ATTRIBUTE_KEY_BUCKETING_ID) == 0) {
                    // TODO (Alda): the type for bucketing ID attribute may change so that custom
                    // attributes are not overloaded
                   $feature = [
                        ENTITY_ID => RESERVED_ATTRIBUTE_KEY_BUCKETING_ID,
                        KEY => RESERVED_ATTRIBUTE_KEY_BUCKETING_ID_EVENT_PARAM_KEY,
                        TYPE => CUSTOM_ATTRIBUTE_FEATURE_TYPE,
                        VALUE => $attributeValue
                        ];

                } else {
                    $attributeEntity = $config->getAttribute($attributeKey);
                    if (!is_null($attributeEntity->getKey())) {
                       $feature = [
                            ENTITY_ID => $attributeEntity->getId(),
                            KEY => $attributeKey,
                            TYPE => CUSTOM_ATTRIBUTE_FEATURE_TYPE,
                            VALUE => $attributeValue,
                        ];
                    }
                }
            }

            if(!empty($feature))
                $commonParams[VISITORS][0][ATTRIBUTES][] = $feature;
        }

        return $commonParams;
    }

    /**
     * Helper function to get parameters specific to impression event.
     *
     * @param $experiment Experiment Experiment being activated.
     * @param $variationId string
     */
    private function getImpressionParams(Experiment $experiment, $variationId)
    {
        $impressionParams = [
            DECISIONS => [
                [
                    CAMPAIGN_ID => $experiment->getLayerId(),
                    EXPERIMENT_ID => $experiment->getId(),
                    VARIATION_ID => $variationId                
                ]
            ],

            EVENTS => [
                [
                    ENTITY_ID => $experiment->getLayerId(),
                    TIMESTAMP => time()*1000,
                    KEY => ACTIVATE_EVENT_KEY,
                    UUID => GeneratorUtils::getRandomUuid()
                ]
            ]

        ];

        return $impressionParams;
    }

    /**
     * Helper function to get parameters specific to conversion event.
     *
     * @param $config ProjectConfig Configuration for the project.
     * @param $eventKey string Key representing the event.
     * @param $experimentVariationMap array Map of experiment ID to the ID of the variation that the user is bucketed into.
     * @param $eventTags array Hash representing metadata associated with the event.
     */
    private function getConversionParams($config, $eventKey, $experimentVariationMap, $eventTags)
    {

        $conversionParams = [];
        foreach($experimentVariationMap as $experimentId => $variationId){
            $singleSnapshot = [];
            $experiment = $config->getExperimentFromId($experimentId);
            $eventEntity = $config->getEvent($eventKey);
                        
            $singleSnapshot[DECISIONS] = [
                [
                    CAMPAIGN_ID => $experiment->getLayerId(),
                    EXPERIMENT_ID => $experimentId,
                    VARIATION_ID => $variationId
                ]
            ];

            $singleSnapshot[EVENTS] = [
                [
                    ENTITY_ID => $eventEntity->getId(),
                    TIMESTAMP => time()*1000,
                    UUID => GeneratorUtils::getRandomUuid(),
                    KEY => $eventKey
                ]
            ];

            if(!is_null($eventTags)){
                $revenue = EventTagUtils::getRevenueValue($eventTags);
                if(!is_null($revenue)){
                    $singleSnapshot[EVENTS][0][EventTagUtils::REVENUE_EVENT_METRIC_NAME] = $revenue;
                }

                $eventValue = EventTagUtils::getNumericValue($eventTags);
                if(!is_null($eventValue)){
                    $singleSnapshot[EVENTS][0][EventTagUtils::NUMERIC_EVENT_METRIC_NAME] = $eventValue;
                }

                 $singleSnapshot[EVENTS][0]['tags'] = $eventTags;
            }

            $conversionParams [] = $singleSnapshot;
        }

        return $conversionParams;
    }

    /**
     * Create impression event to be sent to the logging endpoint.
     *
     * @param $config ProjectConfig Configuration for the project.
     * @param $experimentKey Experiment Experiment being activated.
     * @param $variationKey string Variation user
     * @param $userId string ID of user.
     * @param $attributes array Attributes of the user.
     *
     * @return LogEvent Event object to be sent to dispatcher.
     */
    public function createImpressionEvent($config, $experimentKey, $variationKey, $userId, $attributes)
    {
        $eventParams = $this->getCommonParams($config, $userId, $attributes);

        $experiment = $config->getExperimentFromKey($experimentKey);
        $variation = $config->getVariationFromKey($experimentKey, $variationKey);
        $impressionParams = $this->getImpressionParams($experiment, $variation->getId());

        $eventParams[VISITORS][0][SNAPSHOTS][] = $impressionParams;

        return new LogEvent(self::$ENDPOINT, $eventParams, self::$HTTP_VERB, self::$HTTP_HEADERS);
    }

    /**
     * Create conversion event to be sent to the logging endpoint.
     *
     * @param $config ProjectConfig Configuration for the project.
     * @param $eventKey string Key representing the event.
     * @param $experimentVariationMap array Map of experiment ID to the ID of the variation that the user is bucketed into.
     * @param $userId string ID of user.
     * @param $attributes array Attributes of the user.
     * @param $eventTags array Hash representing metadata associated with the event.
     *
     * @return LogEvent Event object to be sent to dispatcher.
     */
    public function createConversionEvent($config, $eventKey, $experimentVariationMap, $userId, $attributes, $eventTags)
    {

        $eventParams = $this->getCommonParams($config, $userId, $attributes);
        $conversionParams = $this->getConversionParams($config, $eventKey, $experimentVariationMap, $eventTags);

        $eventParams[VISITORS][0][SNAPSHOTS] = $conversionParams;
        return new LogEvent(self::$ENDPOINT, $eventParams, self::$HTTP_VERB, self::$HTTP_HEADERS);
    }
}


