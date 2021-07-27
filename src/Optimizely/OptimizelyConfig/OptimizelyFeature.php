<?php
/**
 * Copyright 2020, Optimizely Inc and Contributors
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
namespace Optimizely\OptimizelyConfig;

class OptimizelyFeature implements \JsonSerializable
{
    /**
     * @var string ID representing the feature.
     */
    private $id;

    /**
     * @var string Key representing the feature.
     */
    private $key;

    /**
     * Map of Experiment Keys to OptimizelyExperiments.
     *
     * @var <String, OptimizelyExperiment> associative array
     */
    private $experiment_rules;


    /**
     * Map of rollout Experiments Keys to OptimizelyExperiments.
     *
     * @var <String, OptimizelyExperiment> associative array
     */
    private $delivery_rules;


    /**
     * Map of Experiment Keys to OptimizelyExperiments.
     *
     * @var <String, OptimizelyExperiment> associative array
     */
    private $experimentsMap;

    /**
     * Map of Variable Keys to OptimizelyVariables.
     *
     * @var <String, OptimizelyVariable> associative array
     */
    private $variablesMap;

    public function __construct($id, $key, array $experimentsMap, array $variablesMap, array $experiment_rules, array $delivery_rules)
    {
        $this->id = $id;
        $this->key = $key;
        $this->experiment_rules = $experiment_rules;
        $this->delivery_rules = $delivery_rules;
        $this->experimentsMap = $experimentsMap;
        $this->variablesMap = $variablesMap;
    }

    /**
     * @return string Feature ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string Feature Key.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return array Map of Experiment Keys to OptimizelyExperiments.
     */
    public function getExperimentRules()
    {
        return $this->experiment_rules;
    }

    /**
     * @return array Map of Rollout Experiments Keys to OptimizelyExperiments.
     */
    public function getDeliveryRules()
    {
        return $this->delivery_rules;
    }

    /**
     * @return array Map of Experiment Keys to OptimizelyExperiments.
     */
    public function getExperimentsMap()
    {
        return $this->experimentsMap;
    }
    
    /**
     * @return array Map of Variable Keys to OptimizelyVariables.
     */
    public function getVariablesMap()
    {
        return $this->variablesMap;
    }

    /**
     * @return string JSON representation of the object.
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
