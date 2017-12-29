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

class TrafficAllocation
{
    /**
     * @var string ID representing experiment or variation depending on parent.
     */
    private $_entityId;

    /**
     * @var integer Value representing last bucket number for the experiment or variation.
     */
    private $_endOfRange;

    public function __construct($entityId = null, $endOfRange = null)
    {
        $this->_entityId = $entityId;
        $this->_endOfRange = $endOfRange;
    }

    /**
     * @return string Entity ID representing experiment or variation.
     */
    public function getEntityId()
    {
        return $this->_entityId;
    }

    /**
     * @param $entityId string ID of experiment or group.
     */
    public function setEntityId($entityId)
    {
        $this->_entityId = $entityId;
    }

    /**
     * @return integer End of range for experiment or variation.
     */
    public function getEndOfRange()
    {
        return $this->_endOfRange;
    }

    /**
     * @param $endOfRange integer End of range for experiment or variation.
     */
    public function setEndOfRange($endOfRange)
    {
        $this->_endOfRange = $endOfRange;
    }
}
