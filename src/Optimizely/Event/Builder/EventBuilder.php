<?php
/**
 * Copyright 2016-2021, 2023, Optimizely
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

require 'Params.php';

use Optimizely\Config\ProjectConfigInterface;
use Optimizely\Entity\Attribute;
use Optimizely\Entity\Experiment;
use Optimizely\Enums\ControlAttributes;
use Optimizely\Event\LogEvent;
use Optimizely\Utils\EventTagUtils;
use Optimizely\Utils\GeneratorUtils;
use Optimizely\Utils\Validator;
use phpDocumentor\Reflection\Types\This;

class EventBuilder
{
    /**
     * @const string String denoting SDK type.
     */
    const SDK_TYPE = 'php-sdk';

    /**
     * @const string Version of the Optimizely PHP SDK.
     */
    const SDK_VERSION = '4.0.2';

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
     * @var LoggerInterface Logger for logging messages.
     */
    private $_logger;

    /**
     * Event Builder constructor to set logger
     *
     * @param $logger LoggerInterface
     */
    public function __construct($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Helper function to get parameters common to impression and conversion events.
     *
     * @param $config ProjectConfigInterface Configuration for the project.
     * @param $userId string ID of user.
     * @param $attributes array Attributes of the user.
     *
     * @return array Hash representing parameters which are common to both impression and conversion events.
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
            CLIENT_VERSION => self::SDK_VERSION,
            ANONYMIZE_IP => $config->getAnonymizeIP(),
            ENRICH_DECISIONS => true
        ];

        if (!is_null($attributes)) {
            foreach ($attributes as $attributeKey => $attributeValue) {
                // Omit attributes that are not supported by the log endpoint.
                if (Validator::isAttributeValid($attributeKey, $attributeValue)) {
                    $attributeEntity = $config->getAttribute($attributeKey);
                    if ($attributeEntity instanceof Attribute) {
                        $feature = [
                            ENTITY_ID => $attributeEntity->getId(),
                            KEY => $attributeKey,
                            TYPE => CUSTOM_ATTRIBUTE_FEATURE_TYPE,
                            VALUE => $attributeValue
                        ];

                        $commonParams[VISITORS][0][ATTRIBUTES][] = $feature;
                    }
                }
            }
        }

        // Append Bot Filtering attribute
        $botFilteringValue = $config->getBotFiltering();
        if (is_bool($botFilteringValue)) {
            $feature = [
                ENTITY_ID => ControlAttributes::BOT_FILTERING,
                KEY => ControlAttributes::BOT_FILTERING,
                TYPE => CUSTOM_ATTRIBUTE_FEATURE_TYPE,
                VALUE => $botFilteringValue
            ];

            $commonParams[VISITORS][0][ATTRIBUTES][] = $feature;
        }

        return $commonParams;
    }

    /**
     * Helper function to get parameters specific to impression event.
     *
     * @param $experiment Experiment Experiment being activated.
     * @param $variation Variation representing the variation for the user is allocated.
     * @param $flagKey string feature flag key.
     * @param $ruleKey string feature or rollout experiment key.
     * @param $ruleType string feature or rollout experiment source type.
     * @param $enabled Boolean feature enabled.
     *
     * @return array Hash representing parameters particular to impression event.
     */
    private function getImpressionParams(Experiment $experiment, $variation, $flagKey, $ruleKey, $ruleType, $enabled)
    {
        $variationKey = '';
        $variationId = null;
        if ($variation) {
            $variationKey = $variation->getKey() ? $variation->getKey() : '';
            $variationId = $variation->getId();
        }
        $experimentID = '';
        $campaignID = '';
        if ($experiment) {
            if ($experiment->getId()) {
                $experimentID = $experiment->getId();
                $campaignID = $experiment->getLayerId();
            }
        }
        $impressionParams = [
            DECISIONS => [
                [
                    CAMPAIGN_ID => $campaignID,
                    EXPERIMENT_ID => $experimentID,
                    VARIATION_ID => $variationId,
                    METADATA => [
                        FLAG_KEY => $flagKey,
                        RULE_KEY => $ruleKey,
                        RULE_TYPE => $ruleType,
                        VARIATION_KEY => $variationKey,
                        ENABLED => $enabled
                    ],
                ]
            ],

            EVENTS => [
                [
                    ENTITY_ID => $experiment->getLayerId(),
                    TIMESTAMP => time()*1000,
                    UUID => GeneratorUtils::getRandomUuid(),
                    KEY => ACTIVATE_EVENT_KEY
                ]
            ]

        ];

        return $impressionParams;
    }

    /**
     * Helper function to get parameters specific to conversion event.
     *
     * @param $eventEntity Event representing event entity.
     * @param $eventTags array Hash representing metadata associated with the event.
     *
     * @return array Hash representing parameters particular to conversion event.
     */
    private function getConversionParams($eventEntity, $eventTags)
    {
        $conversionParams = [];
        $singleSnapshot = [];

        $eventDict = [
            ENTITY_ID => $eventEntity->getId(),
            TIMESTAMP => time()*1000,
            UUID => GeneratorUtils::getRandomUuid(),
            KEY => $eventEntity->getKey()
        ];

        if (!is_null($eventTags)) {
            $revenue = EventTagUtils::getRevenueValue($eventTags, $this->_logger);
            if (!is_null($revenue)) {
                $eventDict[EventTagUtils::REVENUE_EVENT_METRIC_NAME] = $revenue;
            }

            $eventValue = EventTagUtils::getNumericValue($eventTags, $this->_logger);
            if (!is_null($eventValue)) {
                $eventDict[EventTagUtils::NUMERIC_EVENT_METRIC_NAME] = $eventValue;
            }

            if (count($eventTags) > 0) {
                $eventDict['tags'] = $eventTags;
            }
        }

        $singleSnapshot[EVENTS] [] = $eventDict;
        $conversionParams [] = $singleSnapshot;

        return $conversionParams;
    }

    /**
     * Create impression event to be sent to the logging endpoint.
     *
     * @param $config ProjectConfigInterface Configuration for the project.
     * @param $experimentId Experiment Experiment being activated.
     * @param $variationKey string Variation user
     * @param $userId string ID of user.
     * @param $attributes array Attributes of the user.
     *
     * @return LogEvent Event object to be sent to dispatcher.
     */
    public function createImpressionEvent($config, $experimentId, $variationKey, $flagKey, $ruleKey, $ruleType, $enabled, $userId, $attributes)
    {
        $eventParams = $this->getCommonParams($config, $userId, $attributes);
        $experiment = $config->getExperimentFromId($experimentId);

        // When variation is not mapped to any flagKey
        $variation = $config->getVariationFromKeyByExperimentId($experimentId, $variationKey);

        // Mapped flagKey can be directly used in variation in that case no experimentKey exist
        if ($variation && !$variation->getKey()) {
            $variation = $config->getFlagVariationByKey($flagKey, $variationKey);
        }
        $impressionParams = $this->getImpressionParams($experiment, $variation, $flagKey, $ruleKey, $ruleType, $enabled);

        $eventParams[VISITORS][0][SNAPSHOTS][] = $impressionParams;

        return new LogEvent(self::$ENDPOINT, $eventParams, self::$HTTP_VERB, self::$HTTP_HEADERS);
    }

    /**
     * Create conversion event to be sent to the logging endpoint.
     *
     * @param $config ProjectConfigInterface Configuration for the project.
     * @param $eventKey string Key representing the event.
     * @param $userId string ID of user.
     * @param $attributes array Attributes of the user.
     * @param $eventTags array Hash representing metadata associated with the event.
     *
     * @return LogEvent Event object to be sent to dispatcher.
     */
    public function createConversionEvent($config, $eventKey, $userId, $attributes, $eventTags)
    {
        $eventParams = $this->getCommonParams($config, $userId, $attributes);
        $eventEntity = $config->getEvent($eventKey);
        $conversionParams = $this->getConversionParams($eventEntity, $eventTags);

        $eventParams[VISITORS][0][SNAPSHOTS] = $conversionParams;
        return new LogEvent(self::$ENDPOINT, $eventParams, self::$HTTP_VERB, self::$HTTP_HEADERS);
    }
}
