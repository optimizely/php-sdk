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

class OptimizelyVariable implements \JsonSerializable
{
    /**
     * @var string ID representing variable.
     */
    private $id;

    /**
     * @var string Key representing variable.
     */
    private $key;

     /**
     * @var String Variable denoting type of the variable. One of
     *             boolean, integer, double or string.
     */
    private $type;

    /**
     * @var string Value of the variable.
     */
    private $value;

    public function __construct($id, $key, $type, $value)
    {
        $this->id = $id;
        $this->key = $key;
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * @return string Variable ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string Variable key.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string Variable type.
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * @return string Variable value.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string JSON representation of the object.
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
