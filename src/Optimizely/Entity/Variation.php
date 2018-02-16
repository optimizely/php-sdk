<?php
/**
 * Copyright 2016-2018, Optimizely
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

use Optimizely\Utils\ConfigParser;

class Variation
{
    /**
     * @var string ID representing the variation.
     */
    private $_id;

    /**
     * @var string Unique key of the variation.
     */
    private $_key;

    /**
     * list of all VariableUsage instances that are part of this variation.
     *
     * @var [VariableUsage]
     */
    private $_variableUsageInstances;

    /**
     * map of Feature Variable IDs to Variable Usages constructed during the initialization
     * of Variation objects from the list of Variable Usages.
     *
     * @var <String, VariableUsage>  associative array
     */
    private $_variableIdToVariableUsageInstanceMap;


    public function __construct($id = null, $key = null, $featureEnabled = false, $variableUsageInstances = [])
    {
        $this->_id = $id;
        $this->_key = $key;
        $this->_featureEnabled = $featureEnabled;

        $this->_variableUsageInstances = ConfigParser::generateMap($variableUsageInstances, null, VariableUsage::class);

        $this->generateVariableIdToVariableUsageMap();
    }

    /**
     * @return string Variation ID.
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param $id string ID for variation.
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string Variation key.
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * @param $key string Key for variation.
     */
    public function setKey($key)
    {
        $this->_key = $key;
    }

    /**
     * @return boolean featureEnabled property
     */
    public function getFeatureEnabled()
    {
        return $this->_featureEnabled;
    }

    /**
     * @param boolean $flag
     */
    public function setFeatureEnabled($flag)
    {
        $this->_featureEnabled = $flag;
    }

    /**
     * @return [VariableUsage] Variable usage instances in this variation
     */
    public function getVariables()
    {
        return $this->_variableUsageInstances;
    }

    /**
     * @param  string Variable ID
     *
     * @return VariableUsage Variable usage instance corresponding to given variable ID
     */
    public function getVariableUsageById($variableId)
    {
        if (isset($this->_variableIdToVariableUsageInstanceMap[$variableId])) {
            return $this->_variableIdToVariableUsageInstanceMap[$variableId];
        } else {
            return null;
        }
    }

    /**
     * @param [VariableUsage] array of variable usage instances
     */
    public function setVariables($variableUsageInstances)
    {
        $this->_variableUsageInstances = ConfigParser::generateMap($variableUsageInstances, null, VariableUsage::class);
        $this->generateVariableIdToVariableUsageMap();
    }

    /**
     * Generates variable ID to Variable usage instance map
     * from variable usage instances
     */
    private function generateVariableIdToVariableUsageMap()
    {
        if (!empty($this->_variableUsageInstances)) {
            foreach (array_values($this->_variableUsageInstances) as $variableUsage) {
                $this->_variableIdToVariableUsageInstanceMap[$variableUsage->getId()] = $variableUsage;
            }
        }
    }
}
