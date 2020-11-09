<?php
/**
 * Copyright 2020, Optimizely
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
namespace Optimizely;

class OptimizelyUserContext
{
    private $_optimizelyClient;
    private $_userId;
    private $_userAttributes;

    public function __construct($optimizelyClient, $userId, $userAttributes)
    {
        $this->_optimizelyClient = $optimizelyClient;
        $this->_userId = $userId;
        $this->_userAttributes = $userAttributes;
    }

    public function setAttributes($key, $value)
    {
        $this->_userAttributes[$key] = $value;
    }

    public function decide($key, $options = null)
    {

    }

    public function decideAll($options = null)
    {

    }

    public function decideForKeys($keys, $options = null)
    {

    }

    public function trackEvent($key, $tags = null)
    {

    }
}
