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

class OptimizelyAttribute implements \JsonSerializable
{
    /**
     * @var string id representing attribute.
     */
    private $id;

    /**
     * @var string key representing attribute.
     */
    private $key;

    public function __construct($id, $key)
    {
        $this->id = $id;
        $this->key = $key;
    }

    /**
     * @return string attribute id.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string attribute key.
     */
    public function getKey()
    {
        return $this->key;
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
