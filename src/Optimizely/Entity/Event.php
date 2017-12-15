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

class Event
{
    /**
     * @var string Event ID.
     */
    private $_id;

    /**
     * @var string Event key.
     */
    private $_key;

    /**
     * @var array Experiments using the event.
     */
    private $_experimentIds;


    public function __construct($id = null, $key = null, $experimentIds = null)
    {
        $this->_id = $id;
        $this->_key = $key;
        $this->_experimentIds = $experimentIds;
    }

    /**
     * @return string ID of event.
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param $id string ID for event.
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string Event key.
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * @param $key string Key for event.
     */
    public function setKey($key)
    {
        $this->_key = $key;
    }

    /**
     * @return array Experiments using event.
     */
    public function getExperimentIds()
    {
        return $this->_experimentIds;
    }

    /**
     * @param $experimentIds array Experiments using event.
     */
    public function setExperimentIds($experimentIds)
    {
        $this->_experimentIds = $experimentIds;
    }
}
