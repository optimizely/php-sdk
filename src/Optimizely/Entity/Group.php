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

class Group
{
    /**
     * @var string Group ID.
     */
    private $_id;

    /**
     * @var string Group policy.
     */
    private $_policy;

    /**
     * @var array Experiments in the group.
     */
    private $_experiments;

    /**
     * @var array Traffic allocation of experiments in the group.
     */
    private $_trafficAllocation;


    public function __construct($id = null, $policy = null, $experiments = null, $trafficAllocation = null)
    {
        $this->_id = $id;
        $this->_policy = $policy;
        $this->_experiments = $experiments;
        $this->_trafficAllocation = $trafficAllocation;
    }

    /**
     * @return string ID of the group.
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param $id string ID for group.
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string Policy of the group.
     */
    public function getPolicy()
    {
        return $this->_policy;
    }

    /**
     * @param $policy string Policy for group.
     */
    public function setPolicy($policy)
    {
        $this->_policy = $policy;
    }

    /**
     * @return array Experiments in the group.
     */
    public function getExperiments()
    {
        return $this->_experiments;
    }

    /**
     * @param $experiments array Experiments in the group.
     */
    public function setExperiments($experiments)
    {
        $this->_experiments = $experiments;
    }

    /**
     * @return array Traffic allocation of experiments in group.
     */
    public function getTrafficAllocation()
    {
        return $this->_trafficAllocation;
    }

    /**
     * @param $trafficAllocation array Traffic allocation of experiments in group.
     */
    public function setTrafficAllocation($trafficAllocation)
    {
        $this->_trafficAllocation = ConfigParser::generateMap($trafficAllocation, null, TrafficAllocation::class);
    }
}
