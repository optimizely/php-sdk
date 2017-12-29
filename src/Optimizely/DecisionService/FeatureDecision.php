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
namespace Optimizely\DecisionService;

class FeatureDecision
{
    const DECISION_SOURCE_EXPERIMENT = 'experiment';
    const DECISION_SOURCE_ROLLOUT = 'rollout';

    /**
     * The experiment in this decision.
     *
     * @var Experiment
     */
    private $_experiment;

    /**
     * The variation in this decision.
     *
     * @var Variation
     */
    private $_variation;

    /**
     * The source of the decision. Either DECISION_SOURCE_EXPERIMENT or DECISION_SOURCE_ROLLOUT
     *
     * @var string
     */
    private $_source;

    /**
     * FeatureDecision constructor.
     *
     * @param $experiment
     * @param $variation
     * @param $source
     */
    public function __construct($experiment, $variation, $source)
    {
        $this->_experiment = $experiment;
        $this->_variation = $variation;
        $this->_source = $source;
    }

    public function getExperiment()
    {
        return $this->_experiment;
    }

    public function getVariation()
    {
        return $this->_variation;
    }

    public function getSource()
    {
        return $this->_source;
    }
}
