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

class OptimizelyExperiment implements \JsonSerializable
{
    /**
     * @var string ID representing Experiment.
     */
    private $id;

    /**
     * @var string Key representing Experiment.
     */
    private $key;

    /**
     * @var string string representing Experiment audience conditions name mapped.
     */
    private $audiences;


    /**
     * Map of Variation Keys to OptimizelyVariations.
     *
     * @var <String, OptimizelyVariation> associative array
     */
    private $variationsMap;

    public function __construct($id, $key, $variationsMap, $audiences)
    {
        $this->id = $id;
        $this->key = $key;
        $this->audiences = $audiences;
        $this->variationsMap = $variationsMap;
    }

    /**
     * @return string Experiment ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string Experiment key.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string Experiment audiences.
     */
    public function getExperimentAudiences()
    {
        return $this->audiences;
    }


    /**
     * @return array Map of Variation Keys to OptimizelyVariations.
     */
    public function getVariationsMap()
    {
        return $this->variationsMap;
    }

    /**
     * @return string JSON representation of the object.
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
