<?php
/**
 * Copyright 2016, Optimizely
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

namespace Optimizely\Entity;

class Audience
{
    /**
     * @var string Audience ID.
     */
    private $_id;

    /**
     * @var string Audience name.
     */
    private $_name;

    /**
     * @var string Audience conditions.
     */
    private $_conditions;

    /**
     * @var array De-serialized audience conditions
     */
    private $_conditionsList;


    public function __construct($id = null, $name = null, $conditions = null)
    {
        $this->_id = $id;
        $this->_name = $name;
        $this->_conditions = $conditions;
    }

    /**
     * @return string ID of audience.
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param $id string ID for audience.
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string Audience name.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param $name string Audience name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @return string Conditions building the audience.
     */
    public function getConditions()
    {
        return $this->_conditions;
    }

    /**
     * @param $conditions string Audience conditions.
     */
    public function setConditions($conditions)
    {
        $this->_conditions = $conditions;
    }

    /**
     * @return array De-serialized audience conditions.
     */
    public function getConditionsList()
    {
        return $this->_conditionsList;
    }

    /**
     * @param $conditionsList array De-serialized audience conditions.
     */
    public function setConditionsList($conditionsList)
    {
        $this->_conditionsList = $conditionsList;
    }
}
