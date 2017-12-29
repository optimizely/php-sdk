<?php
/**
 * Copyright 2017, Optimizely Inc and Contributors
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

namespace Optimizely\UserProfile;

class UserProfile
{
    /**
     * @var string The ID of the user.
     */
    private $_userId;

    /**
     * @var array Bucketing decisions for the user.
     */
    private $_experiment_bucket_map;

    /**
     * UserProfile constructor.
     *
     * @param $userId
     * @param $experimentBucketMap
     */
    public function __construct($userId, $experiment_bucket_map = array())
    {
        $this->_userId = $userId;
        $this->_experiment_bucket_map = $experiment_bucket_map;
    }

    /**
     * @return string ID of the user.
     */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
     * @return array Experiment to decision map.
     */
    public function getExperimentBucketMap()
    {
        return $this->_experiment_bucket_map;
    }

    /**
     * Get the variation ID for the given experiment from the experiment bucket map.
     *
     * @param $experimentId string The ID of the experiment.
     *
     * @return null|string The variation ID.
     */
    public function getVariationForExperiment($experimentId)
    {
        $decision = $this->getDecisionForExperiment($experimentId);
        if (!is_null($decision)) {
            return $decision->getVariationId();
        }

        return null;
    }

    /**
     * Get the decision for the given experiment from the experiment bucket map.
     *
     * @param $experimentId string The ID of the experiment.
     *
     * @return null|Decision The decision for the given experiment.
     */
    public function getDecisionForExperiment($experimentId)
    {
        if (isset($this->_experiment_bucket_map[$experimentId])) {
            return $this->_experiment_bucket_map[$experimentId];
        }

        return null;
    }

    /**
     * Set the decision for the given experiment.
     *
     * @param $experimentId string   The ID of the experiment.
     * @param $decision     Decision The decision for the experiment.
     */
    public function saveDecisionForExperiment($experimentId, Decision $decision)
    {
        $this->_experiment_bucket_map[$experimentId] = $decision;
    }
}
