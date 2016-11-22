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

use Optimizely\Entity\Attribute;
use Optimizely\Entity\Audience;
use Optimizely\Entity\Event;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\Group;
use Optimizely\Entity\Variation;
use Optimizely\Utils\ConditionDecoder;
use Optimizely\Utils\ConfigParser;

/**
 * Class ProjectConfig
 *
 * @package Optimizely
 */
class ProjectConfig
{
    /**
     * @var string Version of the datafile.
     */
    private $_version;

    /**
     * @var string Account ID of the account using the SDK.
     */
    private $_accountId;

    /**
     * @var string Project ID of the Full Stack project.
     */
    private $_projectId;

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
     * ProjectConfig constructor to load and set project configuration data.
     *
     * @param $datafile string JSON string representing the project.
     */
    public function __construct($datafile)
    {
        $config = json_decode($datafile, true);
        $this->_version = $config['version'];
        $this->_accountId = $config['accountId'];
        $this->_projectId = $config['projectId'];
        $this->_revision = $config['revision'];

        $groups = $config['groups'];
        $experiments = $config['experiments'];
        $events = $config['events'];
        $attributes = $config['attributes'];
        $audiences = $config['audiences'];

        $this->_groupIdMap = ConfigParser::generateMap($groups, 'id', Group::class);
        $this->_experimentKeyMap = ConfigParser::generateMap($experiments, 'key', Experiment::class);
        $this->_eventKeyMap = ConfigParser::generateMap($events, 'key', Event::class);
        $this->_attributeKeyMap = ConfigParser::generateMap($attributes, 'key', Attribute::class);
        $this->_audienceIdMap = ConfigParser::generateMap($audiences, 'id', Audience::class);

        forEach(array_values($this->_groupIdMap) as $group) {
            $experimentsInGroup = ConfigParser::generateMap($group->getExperiments(), 'key', Experiment::class);
            forEach(array_values($experimentsInGroup) as $experiment) {
                $experiment->setGroupId($group->getId());
                $experiment->setGroupPolicy($group->getPolicy());
            }
            $this->_experimentKeyMap = array_merge($this->_experimentKeyMap, $experimentsInGroup);
        }

        forEach(array_values($this->_experimentKeyMap) as $experiment) {
            $this->_variationKeyMap[$experiment->getKey()] = [];
            $this->_variationIdMap[$experiment->getKey()] = [];
            $this->_experimentIdMap[$experiment->getId()] = $experiment;

            forEach($experiment->getVariations() as $variation) {
                $this->_variationKeyMap[$experiment->getKey()][$variation->getKey()] = $variation;
                $this->_variationIdMap[$experiment->getKey()][$variation->getId()] = $variation;
            }
        }

        $conditionDecoder = new ConditionDecoder();
        forEach(array_values($this->_audienceIdMap) as $audience) {
            $conditionDecoder->deserializeAudienceConditions($audience->getConditions());
            $audience->setConditionsList($conditionDecoder->getConditionsList());
        }
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

        return new Experiment();
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

        return new Event();
    }

    /**
     * @param $audienceId string ID of the audience.
     *
     * @return Audience Entity corresponding to the ID.
     *         Dummy entity is returned if ID is invalid.
     */
    public function getAudience($audienceId)
    {
        if (isset($this->_audienceIdMap[$audienceId])) {
            return $this->_audienceIdMap[$audienceId];
        }

        return new Audience();
    }

    /**
     * @param $attributeKey string Key of the attribute.
     *
     * @return Attribute Entity corresponding to the key.
     *         Dummy entity is returned if key is invalid.
     */
    public function getAttribute($attributeKey)
    {
        if (isset($this->_attributeKeyMap[$attributeKey])) {
            return $this->_attributeKeyMap[$attributeKey];
        }

        return new Attribute();
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
        if(isset($this->_variationKeyMap[$experimentKey]) &&
            isset($this->_variationKeyMap[$experimentKey][$variationKey])) {
            return $this->_variationKeyMap[$experimentKey][$variationKey];
        }

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
        if(isset($this->_variationIdMap[$experimentKey]) &&
            isset($this->_variationIdMap[$experimentKey][$variationId])) {
            return $this->_variationIdMap[$experimentKey][$variationId];
        }

        return new Variation();
    }
}
