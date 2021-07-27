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

class OptimizelyAudience implements \JsonSerializable
{
    /**
     * @var string ID representing Audience.
     */
    private $id;

    /**
     * @var string Name representing Audience.
     */
    private $name;

    /**
     * @var string Conditions representing Audience conditions.
     */
    private $conditions;


    public function __construct($id, $name, $conditions)
    {
        $this->id = $id;
        $this->name = $name;
        $this->conditions = $conditions;
    }

    /**
     * @return string Audience ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string Audience Name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string Audience conditions.
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @return string JSON representation of the object.
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
