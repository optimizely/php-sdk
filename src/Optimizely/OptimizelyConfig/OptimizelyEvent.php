<?php
/**
 * Copyright 2021, Optimizely Inc and Contributors
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

class OptimizelyEvent implements \JsonSerializable
{
    /**
     * @var string id representing event.
     */
    private $id;

    /**
     * @var string key representing event.
     */
    private $key;

    /**
     * @var array experimentIds representing event experiment ids.
     */
    private $experimentIds;


    public function __construct($id, $key, array $experimentIds)
    {
        $this->id = $id;
        $this->key = $key;
        $this->experimentIds = $experimentIds;
    }

    /**
     * @return string event ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string event Key.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return array experimentIds representing event experiment ids.
     */
    public function getExperimentIds()
    {
        return $this->experimentIds;
    }

    /**
     * @return string JSON representation of the object.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
