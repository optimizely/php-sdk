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

namespace Optimizely\Entity;

use Optimizely\Utils\ConfigParser;

class Experiment
{
    /**
     * @const string String denoting running state of experiment.
     */
    const STATUS_RUNNING = 'Running';

    /**
     * @const string String denoting policy of mutually exclusive group.
     */
    const MUTEX_GROUP_POLICY = 'random';

    /**
     * @var string Experiment ID.
     */
    private $_id;

    /**
     * @var string Experiment key.
     */
    private $_key;

    /**
     * @var string Experiment status.
     */
    private $_status;

    /**
     * @var string Layer ID for the experiment.
     */
    private $_layerId;

    /**
     * @var string Group ID for the experiment.
     */
    private $_groupId;

    /**
     * @var array Variations in experiment.
     */
    private $_variations;

    /**
     * @var array Forced variations for experiment.
     */
    private $_forcedVariations;

    /**
     * @var string Policy of the experiment group.
     */
    private $_groupPolicy;

    /**
     * @var array ID(s) of audience(s) the experiment is targeted to.
     */
    private $_audienceIds;

    /**
     * @var array Traffic allocation of variations in the experiment.
     */
    private $_trafficAllocation;


    public function __construct(
        $id = null,
        $key = null,
        $layerId = null,
        $status = null,
        $groupId = null,
        $variations = null,
        $forcedVariations = null,
        $groupPolicy = null,
        $audienceIds = null,
        $trafficAllocation = null
    ) {
        $this->_id = $id;
        $this->_key = $key;
        $this->_status = $status;
        $this->_layerId = $layerId;
        $this->_groupId = $groupId;
        $this->_variations = $variations;
        $this->_forcedVariations = $forcedVariations;
        $this->_groupPolicy = $groupPolicy;
        $this->_audienceIds = $audienceIds;
        $this->_trafficAllocation = $trafficAllocation;
    }

    /**
     * @return string ID of experiment.
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param $id string ID for experiment.
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string Key of experiment.
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * @param $key string Key for experiment.
     */
    public function setKey($key)
    {
        $this->_key = $key;
    }

    /**
     * @return string Status of experiment.
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @param $status string Status for experiment.
     */
    public function setStatus($status)
    {
        $this->_status = $status;
    }

    /**
     * @return string Layer ID of experiment.
     */
    public function getLayerId()
    {
        return $this->_layerId;
    }

    /**
     * @param $layerId string Layer ID for experiment.
     */
    public function setLayerId($layerId)
    {
        $this->_layerId = $layerId;
    }

    /**
     * @return string ID of group experiment belongs to.
     */
    public function getGroupId()
    {
        return $this->_groupId;
    }

    /*
     * @param $groupId Group ID for the experiment.
     */
    public function setGroupId($groupId)
    {
        $this->_groupId = $groupId;
    }

    /**
     * @return array Variations in experiment.
     */
    public function getVariations()
    {
        return $this->_variations;
    }

    /**
     * @param $variations array Variations in experiment.
     */
    public function setVariations($variations)
    {
        $this->_variations = ConfigParser::generateMap($variations, null, Variation::class);
    }

    /**
     * @return array Forced variations for experiment.
     */
    public function getForcedVariations()
    {
        return $this->_forcedVariations;
    }

    /**
     * @param $forcedVariations array Forced variations for experiment.
     */
    public function setForcedVariations($forcedVariations)
    {
        $this->_forcedVariations = $forcedVariations;
    }

    /**
     * @return string Policy of group experiment belongs to.
     */
    public function getGroupPolicy()
    {
        return $this->_groupPolicy;
    }

    /**
     * @param $groupPolicy Group policy of the experiment's group.
     */
    public function setGroupPolicy($groupPolicy)
    {
        $this->_groupPolicy = $groupPolicy;
    }

    /**
     * @return array Audiences used by experiment.
     */
    public function getAudienceIds()
    {
        return $this->_audienceIds;
    }

    /**
     * @param $audienceIds array Audiences to which experiment is targeted.
     */
    public function setAudienceIds($audienceIds)
    {
        $this->_audienceIds = $audienceIds;
    }

    /**
     * @return array Traffic allocation of variations in experiment.
     */
    public function getTrafficAllocation()
    {
        return $this->_trafficAllocation;
    }

    /**
     * @param $trafficAllocation array Traffic allocation of variations in experiment.
     */
    public function setTrafficAllocation($trafficAllocation)
    {
        $this->_trafficAllocation = ConfigParser::generateMap($trafficAllocation, null, TrafficAllocation::class);
    }

    /**
     * Determine if experiment is in a mutually exclusive group.
     *
     * @return boolean True if in a mutually exclusive group. False otherwise.
     */
    public function isInMutexGroup()
    {
        return !is_null($this->_groupPolicy) && $this->_groupPolicy == self::MUTEX_GROUP_POLICY;
    }

    /**
     * Determine if experiment is running or not.
     *
     * @return boolean True if experiment has status "Running". False otherwise.
     */
    public function isExperimentRunning()
    {
        return !is_null($this->_status) && $this->_status == self::STATUS_RUNNING;
    }

    /**
     * Determine if user is in forced variation of experiment.
     *
     * @param  $userId string ID of the user.
     * @return boolean True if user is in forced variation of experiment. False otherwise.
     */
    public function isUserInForcedVariation($userId)
    {
        $forcedVariations = $this->getForcedVariations();
        return !is_null($forcedVariations) && isset($forcedVariations[$userId]);
    }
}
