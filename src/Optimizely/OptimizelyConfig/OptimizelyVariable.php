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

class OptimizelyVariable implements \JsonSerializable
{   
    private $id;

    private $key;

    private $type;

    private $value;

    public function __construct($id, $key, $type, $value)
    {
        $this->id = $id;
        $this->key = $key;
        $this->type = $type;
        $this->value = $value;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getType()
    {
        return $this->type;
    }
    
    public function getValue()
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}