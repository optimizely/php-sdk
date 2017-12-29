<?php
/**
 * Copyright 2017, Optimizely Inc and Contributors
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

namespace Optimizely\UserProfile;

class Decision
{
    /**
     * @var string The ID variation in this decision.
     */
    private $_variationId;

    /**
     * Decision constructor.
     *
     * @param $variationId
     */
    public function __construct($variationId)
    {
        $this->_variationId = $variationId;
    }

    public function getVariationId()
    {
        return $this->_variationId;
    }

    public function setVariationId($variationId)
    {
        $this->_variationId = $variationId;
    }
}
