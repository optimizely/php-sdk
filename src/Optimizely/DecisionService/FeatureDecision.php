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
    * @var string The ID experiment in this decision.
    */
    private $_experimentId;

    /**
    * @var string The ID variation in this decision.
    */
    private $_variationId;

    /**
    * The source of the decision. Either DECISION_SOURCE_EXPERIMENT or DECISION_SOURCE_ROLLOUT
    * @var string
    */
    private $_source;

    /**
    * FeatureDecision constructor.
    *
    * @param $experimentId
    * @param $variationId
    * @param $source
    */
    public function __construct($experimentId, $variationId, $source)
    {
        $this->_experimentId = $experimentId;
        $this->_variationId = $variationId;
        $this->_source = $source;
    }

    public function getExperimentId()
    {
        return $this->_experimentId;
    }

    public function getVariationId()
    {
        return $this->_variationId;
    }

    public function getSource()
    {
        return $this->_source;
    }
}
