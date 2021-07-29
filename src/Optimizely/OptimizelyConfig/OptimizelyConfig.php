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

class OptimizelyConfig implements \JsonSerializable
{
    /**
     * @var string environmentKey of the config.
     */
    private $environment_key;

    /**
     * @var string sdkKey of the config.
     */
    private $sdk_key;

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
     * array of attributes as OptimizelyAttribute.
     *
     * @var <String, OptimizelyAttribute> associative array
     */
    private $attributes;

    /**
     * array of audiences as OptimizelyAudience.
     *
     * @var <String, OptimizelyAudience> associative array
     */
    private $audiences;

    /**
     * array of events as OptimizelyEvent.
     *
     * @var <String, OptimizelyEvent> associative array
     */
    private $events;

    /**
     * @var string Contents of datafile.
     */
    private $datafile;
    

    public function __construct($revision, array $experimentsMap, array $featuresMap, $datafile = null, $environment_key='', $sdk_key='', array $attributes=[], array $audiences=[], array $events=[])
    {
        $this->environment_key = $environment_key;
        $this->sdk_key = $sdk_key;
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
        return $this->environment_key;
    }

    /**
     * @return string Config sdkKey.
     */
    public function getSdkKey()
    {
        return $this->sdk_key;
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
     * @return array attributes as  OptimizelyAttribute.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return array audiences as  OptimizelyAudience.
     */
    public function getAudiences()
    {
        return $this->audiences;
    }

    /**
     * @return array events as  OptimizelyEvent.
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
