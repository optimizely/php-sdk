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

class OptimizelyConfig implements \JsonSerializable
{   
    private $revision;

    private $experimentsMap;

    private $featuresMap;

    public function __construct($revision, array $experimentsMap, array $featuresMap)
    {
        $this->revision = $revision;
        $this->experimentsMap = $experimentsMap;
        $this->featuresMap = $featuresMap;
    }

    public function getRevision()
    {
        return $this->revision;
    }

    public function getExperimentsMap()
    {
        return $this->experimentsMap;
    }

    public function getFeaturesMap()
    {
        return $this->featuresMap;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}