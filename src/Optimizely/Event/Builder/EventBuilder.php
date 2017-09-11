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

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

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
    const SDK_VERSION = '1.2.0';

    /**
     * @var string URL to send 
     * event to.
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
     * Helper function to get parameters common to impression and conversion event.
     *
     * @param $config ProjectConfig Configuration for the project.
     * @param $userId string ID of user.
     * @param $attributes array Attributes of the user.
     */
    private function getCommonParams($config, $userId, $attributes)
    {
        $visitor = [
            'snapshots'=> [],
            'visitor_id'=> $userId,
            'attributes' => []
        ];

        $commonParams = [
            ACCOUNT_ID => $config->getAccountId(),
            PROJECT_ID => $config->getProjectId(),
            VISITORS => [$visitor],
            REVISION => $config->getRevision(),
            CLIENT_ENGINE => self::SDK_TYPE,
            CLIENT_VERSION => self::SDK_VERSION
        ];



        forEach ($attributes as $attributeKey => $attributeValue) {
            $feature = [];
            if ($attributeValue) {
                // check for reserved attributes
                if (strcmp($attributeKey , RESERVED_ATTRIBUTE_KEY_BUCKETING_ID) == 0) {
                    // TODO (Alda): the type for bucketing ID attribute may change so that custom
                    // attributes are not overloaded
                   $feature = [
                        'entity_id' => RESERVED_ATTRIBUTE_KEY_BUCKETING_ID,
                        'key' => RESERVED_ATTRIBUTE_KEY_BUCKETING_ID_EVENT_PARAM_KEY,
                        'type' => CUSTOM_ATTRIBUTE_FEATURE_TYPE,
                        'value' => $attributeValue
                        ];

                } else {
                    $attributeEntity = $config->getAttribute($attributeKey);
                    if (!is_null($attributeEntity->getKey())) {
                       $feature = [
                            'entity_id' => $attributeEntity->getId(),
                            'key' => $attributeKey,
                            'type' => CUSTOM_ATTRIBUTE_FEATURE_TYPE,
                            'value' => $attributeValue,
                        ];
                    }
                }
            }

            if(!empty($feature))
                $commonParams[VISITORS][0]['attributes'][] = $feature;
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
        try {
            // Generate a version 4 (random) UUID object
            $uuid4 = Uuid::uuid4();
            echo $uuid4->toString() . "\n"; // i.e. 25769c6c-d34d-4bfe-ba98-e0ee856f3e7a
        } catch (UnsatisfiedDependencyException $e) {
            // Some dependency was not met. Either the method cannot be called on a
            // 32-bit system, or it can, but it relies on Moontoast\Math to be present.
            echo 'Caught exception: ' . $e->getMessage() . "\n";
        }

        $impressionParams = [
            'decisions' => [
                [
                    'campaign_id' => $experiment->getLayerId(),
                    'experiment_id' => $experiment->getId(),
                    'variation_id' => $variationId
                
                ]
            ],

            'events' => [
                [
                    'entity_id' => $experiment->getLayerId(),
                    'timestamp' => time()*1000,
                    'key' => ACTIVATE_EVENT_KEY,
                    'uuid' => $uuid4
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
     * @param $userId string ID of user.
     * @param $eventTags array Hash representing metadata associated with the event.
     */
    private function getConversionParams($config, $eventKey, $experimentVariationMap, $userId, $eventTags)
    {

        $conversionParams = [];
        foreach($experimentVariationMap as $experimentId => $variationId){
            $singleSnapshot = [];
            $experiment = $config->getExperimentFromId($experimentId);
            $eventEntity = $config->getEvent($eventKey);
            try {
                // Generate a version 4 (random) UUID object
                $uuid4 = Uuid::uuid4();
                echo $uuid4->toString() . "\n"; // i.e. 25769c6c-d34d-4bfe-ba98-e0ee856f3e7a
            } catch (UnsatisfiedDependencyException $e) {
                // Some dependency was not met. Either the method cannot be called on a
                // 32-bit system, or it can, but it relies on Moontoast\Math to be present.
                echo 'Caught exception: ' . $e->getMessage() . "\n";
            }


            
            $singleSnapshot['decisions'] = [
                [
                    'campaign_id' => $experiment->getLayerId(),
                    'experiment_id' => $experimentId,
                    'variation_id' => $variationId
                ]
            ];

            $singleSnapshot['events'] = [
                [
                    'entity_id' => $eventEntity->getId(),
                    'timestamp' => time()*1000,
                    'uuid' => $uuid4,
                    'key' => $eventKey

                ]
            ];

            if(!is_null($eventTags)){
                
                $revenue = EventTagUtils::getRevenueValue($eventTags);
                if(!is_null($revenue)){
                    $singleSnapshot['events'][0][EventTagUtils::REVENUE_EVENT_METRIC_NAME] = $revenue;
                }

                $eventValue = EventTagUtils::getEventValue($eventTags);
                if(!is_null($eventValue)){
                    $singleSnapshot['events'][0][EventTagUtils::NUMERIC_EVENT_METRIC_NAME] = $eventValue;
                }

                 $singleSnapshot['events'][0]['tags'] = $eventTags;
            }

            $conversionParams [] = $singleSnapshot;
        }
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

        $eventParams[VISITORS][0].['snapshots'][] = $impressionParams;

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
        $conversionParams = $this->getConversionParams($config, $eventKey, $experimentVariationMap, $userId, $eventTags);

        $eventParams[VISITORS][0].['snapshots'][] = $conversionParams;
        return new LogEvent(self::$ENDPOINT, $eventParams, self::$HTTP_VERB, self::$HTTP_HEADERS);
    }
}
