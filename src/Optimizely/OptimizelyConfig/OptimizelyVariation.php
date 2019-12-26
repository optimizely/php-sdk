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

class OptimizelyVariation implements \JsonSerializable
{
    /**
     *  @var string ID representing the variation.
     */
    private $id;

    /**
     * @var string Key representing the variation.
     */
    private $key;

    /**
     * @var boolean Flag representing if the feature is enabled.
     */
    private $featureEnabled;

    /**
     * Map of Variable Keys to OptimizelyVariables.
     *
     * @var <String, OptimizelyVariable> associative array
     */
    private $variablesMap;

    public function __construct($id, $key, $featureEnabled, array $variablesMap)
    {
        $this->id = $id;
        $this->key = $key;
        $this->featureEnabled = $featureEnabled;
        $this->variablesMap = $variablesMap;
    }

    /**
     * @return string Variation ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string Variation key.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return boolean featureEnabled property
     */
    public function getFeatureEnabled()
    {
        return $this->featureEnabled;
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
     *                Unsets featureEnabled property for variations of ab experiments.
     */
    public function jsonSerialize()
    {
        $props = get_object_vars($this);
        // featureEnabled prop is irrelevant for ab experiments.
        if ($this->featureEnabled === null) {
            unset($props['featureEnabled']);
        }

        return $props;
    }
}
