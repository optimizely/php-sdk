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

class FeatureVariable
{

    // Feature variable primitive types
    const BOOLEAN_TYPE = 'boolean';
    const STRING_TYPE = 'string';
    const INTEGER_TYPE = 'integer';
    const DOUBLE_TYPE = 'double';

    /**
     * variable to hold the feature variable ID
     *
     * @var String
     */
    private $_id;

    /**
     * variable to hold the feature variable key
     *
     * @var String
     */
    private $_key;

    /**
     * variable denoting what primitive type the variable is. Will be one of 4 possible values:
     * a. boolean
     * b. string
     * c. integer
     * d. double
     *
     * @var String
     */
    private $_type;

    /**
     * variable containing the string representation of the default value for the feature variable.
     * This is the variable value that should be returned if the user is not bucketed into any variations
     * or fails the audience rules.
     *
     * @var String
     */
    private $_defaultValue;


    public function __construct($id =null, $key = null, $type = null, $defaultValue = null)
    {
        $this->_id = $id;
        $this->_key = $key;
        $this->_type = $type;
        $this->_defaultValue = $defaultValue;
    }

    /**
     * @return String Feature variable ID
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param String $id Feature variable ID
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return String Feature variable Key
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * @param String $key Feature variable Key
     */
    public function setKey($key)
    {
        $this->_key = $key;
    }

    /**
     * @return String Feature variable primitive type
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param String $type Feature variable primitive type
     */
    public function setType($type)
    {
        $this->_type = $type;
    }

    /**
     * @return String Default value of the feature variable
     */
    public function getDefaultValue()
    {
        return $this->_defaultValue;
    }

    /**
     * @param String $value Default value of the feature variable
     */
    public function setDefaultValue($value)
    {
        $this->_defaultValue = $value;
    }
}
