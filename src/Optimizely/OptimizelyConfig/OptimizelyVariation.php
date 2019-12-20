<?php
/**
 * Copyright 2019, Optimizely Inc and Contributors
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

    private $id;

    private $key;

    private $featureEnabled;

    private $variablesMap;

    public function __construct($id, $key, $featureEnabled, array $variablesMap)
    {
        $this->id = $id;
        $this->key = $key;
        $this->featureEnabled = $featureEnabled;
        $this->variablesMap = $variablesMap;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getFeatureEnabled()
    {
        return $this->featureEnabled;
    }
    
    public function getVariablesMap()
    {
        return $this->variablesMap;
    }

    public function jsonSerialize()
    {
        $props = get_object_vars($this);
        if ($this->featureEnabled === null) {
            unset($props['featureEnabled']);
        }

        return $props;
    }
}
