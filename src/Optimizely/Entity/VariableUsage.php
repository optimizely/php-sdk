<?php
/**
 * Copyright 2017, Optimizely
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

class VariableUsage
{

    /**
     * The ID of the live variable this usage is modifying
     *
     * @var String
     */
    private $_id;

    /**
     * the variable value for users in this particular variation
     *
     * @var String
     */
    private $_value;

    public function __construct($id = null, $value = null)
    {
        $this->_id = $id;
        $this->_value = $value;
    }

    /**
     * @return String ID of the live variable this usage is modifying
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param String $id ID of the live variable this usage is modifying
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return String variable value for users in this particular variation
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * @param String $value variable value for users in this particular variation
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }
}
