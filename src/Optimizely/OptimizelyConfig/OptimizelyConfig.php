<?php
/**
 * Copyright 2020-2021, Optimizely Inc and Contributors
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
    /**
     * @var string environmentKey of the config.
     */
    private $environmentKey;

    /**
     * @var string sdkKey of the config.
     */
    private $sdkKey;

    /**
     * @var string Revision of the config.
     */
    private $revision;

    /**
     * Map of Experiment Keys to OptimizelyExperiments.
     *
     * @var <String, OptimizelyExperiment> associative array
     */
    private $experimentsMap;

    /**
     * Map of Feature Keys to OptimizelyFeatures.
     *
     * @var <String, OptimizelyFeature> associative array
     */
    private $featuresMap;

    /**
     * Array of attributes as OptimizelyAttribute.
     *
     * @var [OptimizelyAttribute]
     */
    private $attributes;

    /**
     * Array of audiences as OptimizelyAudience.
     *
     * @var [OptimizelyAudience]
     */
    private $audiences;

    /**
     * Array of events as OptimizelyEvent.
     *
     * @var [OptimizelyEvent]
     */
    private $events;

    /**
     * @var string Contents of datafile.
     */
    private $datafile;
    

    public function __construct($revision, array $experimentsMap, array $featuresMap, $datafile = null, $environmentKey = '', $sdkKey = '', $attributes = [], $audiences = [], $events = [])
    {
        $this->environmentKey = $environmentKey;
        $this->sdkKey = $sdkKey;
        $this->revision = $revision;
        $this->experimentsMap = $experimentsMap;
        $this->featuresMap = $featuresMap;
        $this->attributes = $attributes;
        $this->audiences = $audiences;
        $this->events = $events;
        $this->datafile = $datafile;
    }

    /**
     * @return string Config environmentKey.
     */
    public function getEnvironmentKey()
    {
        return $this->environmentKey;
    }

    /**
     * @return string Config sdkKey.
     */
    public function getSdkKey()
    {
        return $this->sdkKey;
    }

    /**
     * @return string Config revision.
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * @return string Datafile contents.
     */
    public function getDatafile()
    {
        return $this->datafile;
    }

    /**
     * @return array Map of Experiment Keys to OptimizelyExperiments.
     */
    public function getExperimentsMap()
    {
        return $this->experimentsMap;
    }

    /**
     * @return array Map of Feature Keys to OptimizelyFeatures.
     */
    public function getFeaturesMap()
    {
        return $this->featuresMap;
    }

    /**
     * @return array Attributes as  OptimizelyAttribute.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return array Audiences as  OptimizelyAudience.
     */
    public function getAudiences()
    {
        return $this->audiences;
    }

    /**
     * @return array Events as  OptimizelyEvent.
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @return string JSON representation of the object.
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
